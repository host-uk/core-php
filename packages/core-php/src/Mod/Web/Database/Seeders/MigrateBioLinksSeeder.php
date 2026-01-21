<?php

namespace Core\Mod\Web\Database\Seeders;

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Click;
use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pixel;
use Core\Mod\Tenant\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Migrate BioHost data from AltumCode to Host Hub.
 *
 * AltumCode BioHost Schema (source):
 * - links: biolink pages, short links, file links, vcards, events
 * - biolinks_blocks: content blocks within biolink pages
 * - links_statistics: click tracking
 * - projects: folders for organising links
 * - domains: custom domains
 * - pixels: tracking pixels (Facebook, GA, etc.)
 *
 * @see config/database.php for altum_biohost connection config
 */
class MigrateBioLinksSeeder extends Seeder
{
    protected string $connection = 'altum_biohost';

    protected int $chunkSize = 100;

    protected array $userIdMap = [];

    protected array $projectIdMap = [];

    protected array $domainIdMap = [];

    protected array $pixelIdMap = [];

    protected array $linkIdMap = [];

    protected int $migratedLinks = 0;

    protected int $migratedBlocks = 0;

    protected int $migratedClicks = 0;

    protected int $skippedOrphans = 0;

    public function run(): void
    {
        $this->command->info('Starting BioLinks migration from AltumCode...');

        // Check connection
        if (! $this->testConnection()) {
            $this->command->error('Cannot connect to AltumCode BioHost database. Check ALTUM_BIOHOST_* env vars.');

            return;
        }

        // Build user mapping (AltumCode user_id -> Host Hub user_id)
        $this->buildUserMapping();

        // Migrate in order of dependencies
        $this->migrateProjects();
        $this->migrateDomains();
        $this->migratePixels();
        $this->migrateLinks();
        $this->migrateBlocks();
        $this->migrateClicks();

        $this->command->info('Migration complete:');
        $this->command->info("  Links: {$this->migratedLinks}");
        $this->command->info("  Blocks: {$this->migratedBlocks}");
        $this->command->info("  Clicks: {$this->migratedClicks}");
        $this->command->info("  Skipped orphans: {$this->skippedOrphans}");
    }

    protected function testConnection(): bool
    {
        try {
            DB::connection($this->connection)->getPdo();

            return true;
        } catch (\Exception $e) {
            Log::error('BioLinks migration connection failed: '.$e->getMessage());

            return false;
        }
    }

    protected function buildUserMapping(): void
    {
        $this->command->info('Building user mapping...');

        // Map by email address - users must exist in Host Hub first
        $altumUsers = DB::connection($this->connection)
            ->table('users')
            ->select('user_id', 'email')
            ->get();

        foreach ($altumUsers as $altumUser) {
            $hubUser = User::where('email', $altumUser->email)->first();
            if ($hubUser) {
                $this->userIdMap[$altumUser->user_id] = $hubUser->id;
            }
        }

        $this->command->info('  Mapped '.count($this->userIdMap).' users');
    }

    protected function migrateProjects(): void
    {
        $this->command->info('Migrating projects...');

        DB::connection($this->connection)
            ->table('projects')
            ->orderBy('project_id')
            ->chunk($this->chunkSize, function ($projects) {
                foreach ($projects as $project) {
                    $userId = $this->userIdMap[$project->user_id] ?? null;
                    if (! $userId) {
                        $this->skippedOrphans++;

                        continue;
                    }

                    $newProject = BioLinkProject::create([
                        'user_id' => $userId,
                        'name' => $project->name ?? 'Untitled Project',
                        'color' => $project->color ?? '#6366f1',
                        'created_at' => $project->datetime ?? now(),
                        'updated_at' => $project->last_datetime ?? now(),
                    ]);

                    $this->projectIdMap[$project->project_id] = $newProject->id;
                }
            });

        $this->command->info('  Migrated '.count($this->projectIdMap).' projects');
    }

    protected function migrateDomains(): void
    {
        $this->command->info('Migrating domains...');

        DB::connection($this->connection)
            ->table('domains')
            ->orderBy('domain_id')
            ->chunk($this->chunkSize, function ($domains) {
                foreach ($domains as $domain) {
                    $userId = $this->userIdMap[$domain->user_id] ?? null;
                    if (! $userId) {
                        $this->skippedOrphans++;

                        continue;
                    }

                    // Skip if domain already exists
                    if (BioLinkDomain::where('host', $domain->host)->exists()) {
                        continue;
                    }

                    $newDomain = BioLinkDomain::create([
                        'user_id' => $userId,
                        'host' => $domain->host,
                        'scheme' => $domain->scheme ?? 'https',
                        'custom_index_url' => $domain->custom_index_url ?? null,
                        'custom_not_found_url' => $domain->custom_not_found_url ?? null,
                        'is_enabled' => (bool) ($domain->is_enabled ?? true),
                        'verification_status' => 'verified', // Assume verified if in production
                        'verified_at' => now(),
                        'created_at' => $domain->datetime ?? now(),
                        'updated_at' => $domain->last_datetime ?? now(),
                    ]);

                    $this->domainIdMap[$domain->domain_id] = $newDomain->id;
                }
            });

        $this->command->info('  Migrated '.count($this->domainIdMap).' domains');
    }

    protected function migratePixels(): void
    {
        $this->command->info('Migrating tracking pixels...');

        DB::connection($this->connection)
            ->table('pixels')
            ->orderBy('pixel_id')
            ->chunk($this->chunkSize, function ($pixels) {
                foreach ($pixels as $pixel) {
                    $userId = $this->userIdMap[$pixel->user_id] ?? null;
                    if (! $userId) {
                        $this->skippedOrphans++;

                        continue;
                    }

                    $newPixel = BioLinkPixel::create([
                        'user_id' => $userId,
                        'type' => $this->mapPixelType($pixel->type),
                        'name' => $pixel->name ?? 'Unnamed Pixel',
                        'pixel_id' => $pixel->pixel ?? '',
                        'created_at' => $pixel->datetime ?? now(),
                        'updated_at' => $pixel->last_datetime ?? now(),
                    ]);

                    $this->pixelIdMap[$pixel->pixel_id] = $newPixel->id;
                }
            });

        $this->command->info('  Migrated '.count($this->pixelIdMap).' pixels');
    }

    protected function migrateLinks(): void
    {
        $this->command->info('Migrating links...');

        DB::connection($this->connection)
            ->table('links')
            ->orderBy('link_id')
            ->chunk($this->chunkSize, function ($links) {
                foreach ($links as $link) {
                    $userId = $this->userIdMap[$link->user_id] ?? null;
                    if (! $userId) {
                        $this->skippedOrphans++;

                        continue;
                    }

                    // Map foreign keys
                    $projectId = $this->projectIdMap[$link->project_id ?? 0] ?? null;
                    $domainId = $this->domainIdMap[$link->domain_id ?? 0] ?? null;

                    // Parse settings JSON
                    $settings = [];
                    if (! empty($link->settings)) {
                        $settings = is_string($link->settings)
                            ? json_decode($link->settings, true) ?? []
                            : (array) $link->settings;
                    }

                    // Generate unique URL if collision
                    $url = $link->url ?? Str::random(8);
                    $existingLink = Page::where('domain_id', $domainId)
                        ->where('url', $url)
                        ->exists();
                    if ($existingLink) {
                        $url = $url.'-'.Str::random(4);
                    }

                    $newLink = Page::create([
                        'user_id' => $userId,
                        'project_id' => $projectId,
                        'domain_id' => $domainId,
                        'type' => $link->type ?? 'biolink',
                        'url' => $url,
                        'location_url' => $link->location_url ?? null,
                        'settings' => $settings,
                        'clicks' => $link->clicks ?? 0,
                        'unique_clicks' => $link->unique_visitors ?? 0,
                        'start_date' => $link->start_date ?? null,
                        'end_date' => $link->end_date ?? null,
                        'is_enabled' => (bool) ($link->is_enabled ?? true),
                        'is_verified' => (bool) ($link->is_verified ?? false),
                        'last_click_at' => null, // Will be updated by clicks migration
                        'created_at' => $link->datetime ?? now(),
                        'updated_at' => $link->last_datetime ?? now(),
                    ]);

                    $this->linkIdMap[$link->link_id] = $newLink->id;

                    // Attach pixels if configured
                    if (! empty($link->pixels_ids)) {
                        $pixelIds = is_string($link->pixels_ids)
                            ? json_decode($link->pixels_ids, true) ?? []
                            : (array) $link->pixels_ids;

                        foreach ($pixelIds as $oldPixelId) {
                            $newPixelId = $this->pixelIdMap[$oldPixelId] ?? null;
                            if ($newPixelId) {
                                $newLink->pixels()->attach($newPixelId);
                            }
                        }
                    }

                    $this->migratedLinks++;
                }
            });

        $this->command->info("  Migrated {$this->migratedLinks} links");
    }

    protected function migrateBlocks(): void
    {
        $this->command->info('Migrating blocks...');

        DB::connection($this->connection)
            ->table('biolinks_blocks')
            ->orderBy('biolink_block_id')
            ->chunk($this->chunkSize, function ($blocks) {
                foreach ($blocks as $block) {
                    $linkId = $this->linkIdMap[$block->link_id ?? 0] ?? null;
                    if (! $linkId) {
                        $this->skippedOrphans++;

                        continue;
                    }

                    // Parse settings JSON
                    $settings = [];
                    if (! empty($block->settings)) {
                        $settings = is_string($block->settings)
                            ? json_decode($block->settings, true) ?? []
                            : (array) $block->settings;
                    }

                    Block::create([
                        'biolink_id' => $linkId,
                        'type' => $block->type ?? 'link',
                        'location_url' => $block->location_url ?? null,
                        'settings' => $settings,
                        'order' => $block->order ?? 0,
                        'clicks' => $block->clicks ?? 0,
                        'start_date' => $block->start_date ?? null,
                        'end_date' => $block->end_date ?? null,
                        'is_enabled' => (bool) ($block->is_enabled ?? true),
                        'created_at' => $block->datetime ?? now(),
                        'updated_at' => $block->last_datetime ?? now(),
                    ]);

                    $this->migratedBlocks++;
                }
            });

        $this->command->info("  Migrated {$this->migratedBlocks} blocks");
    }

    protected function migrateClicks(): void
    {
        $this->command->info('Migrating click statistics...');

        // AltumCode stores clicks in links_statistics table
        $tableName = 'links_statistics';

        // Check if table exists
        try {
            DB::connection($this->connection)->table($tableName)->limit(1)->get();
        } catch (\Exception $e) {
            $this->command->warn("  Table {$tableName} not found, skipping click migration");

            return;
        }

        DB::connection($this->connection)
            ->table($tableName)
            ->orderBy('id')
            ->chunk($this->chunkSize * 10, function ($clicks) { // Larger chunks for stats
                foreach ($clicks as $click) {
                    $linkId = $this->linkIdMap[$click->link_id ?? 0] ?? null;
                    if (! $linkId) {
                        $this->skippedOrphans++;

                        continue;
                    }

                    BioLinkClick::create([
                        'biolink_id' => $linkId,
                        'block_id' => null, // AltumCode doesn't track block-level clicks
                        'visitor_hash' => $click->visitor_hash ?? null,
                        'country_code' => $click->country_code ?? null,
                        'region' => $click->city_name ?? null,
                        'device_type' => $this->mapDeviceType($click->device_type ?? null),
                        'os_name' => $click->os_name ?? null,
                        'browser_name' => $click->browser_name ?? null,
                        'referrer_host' => $this->extractReferrerHost($click->referrer ?? null),
                        'utm_source' => $click->utm_source ?? null,
                        'utm_medium' => $click->utm_medium ?? null,
                        'utm_campaign' => $click->utm_campaign ?? null,
                        'is_unique' => (bool) ($click->is_unique ?? false),
                        'created_at' => $click->datetime ?? now(),
                    ]);

                    $this->migratedClicks++;
                }
            });

        $this->command->info("  Migrated {$this->migratedClicks} click records");
    }

    protected function mapPixelType(string $type): string
    {
        // Map AltumCode pixel types to our standard types
        return match (strtolower($type)) {
            'facebook' => 'facebook',
            'google_analytics', 'google-analytics' => 'google_analytics',
            'google_tag_manager', 'gtm' => 'gtm',
            'twitter' => 'twitter',
            'pinterest' => 'pinterest',
            'linkedin' => 'linkedin',
            'tiktok' => 'tiktok',
            'snapchat' => 'snapchat',
            default => $type,
        };
    }

    protected function mapDeviceType(?string $type): string
    {
        if (! $type) {
            return 'other';
        }

        return match (strtolower($type)) {
            'desktop', 'pc' => 'desktop',
            'mobile', 'phone' => 'mobile',
            'tablet', 'ipad' => 'tablet',
            default => 'other',
        };
    }

    protected function extractReferrerHost(?string $referrer): ?string
    {
        if (! $referrer) {
            return null;
        }

        $parsed = parse_url($referrer);

        return $parsed['host'] ?? null;
    }
}
