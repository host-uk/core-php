<?php

namespace Core\Mod\Tenant\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Core\Mod\Analytics\Models\Website as AnalyticsWebsite;
use Core\Mod\Notify\Models\PushWebsite;
use Core\Mod\Support\Models\Mailbox;
use Core\Mod\Tenant\Enums\UserTier;
use Core\Mod\Tenant\Models\Package;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Models\WorkspacePackage;
use Core\Mod\Trust\Models\Campaign as TrustCampaign;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page as BioPage;

class SystemUserSeeder extends Seeder
{
    /**
     * Seed the system user (user ID 1) with full Hades access.
     * This ensures platform owner always has access to everything.
     */
    public function run(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // Get or create user 1 (may already exist from WorkspaceSeeder)
        $user = User::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Snider',
                'email' => 'snider@host.uk.com',
                'password' => Hash::make('change-me-in-env'),
                'tier' => UserTier::HADES,
                'tier_expires_at' => null, // Never expires
                'email_verified_at' => now(),
            ]
        );

        // Environment-aware domain
        $isLocal = app()->environment('local');
        $host = $isLocal ? 'host.test' : 'host.uk.com';

        // Create default workspace if none exists
        $workspace = Workspace::firstOrCreate(
            ['slug' => 'system'],
            [
                'name' => 'System',
                'domain' => $host,
                'icon' => 'shield-check',
                'color' => 'red',
                'description' => 'System workspace for platform administration',
                'type' => 'custom',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        // Attach user to workspace as owner (or update if exists)
        if ($workspace->users()->where('user_id', $user->id)->exists()) {
            // Update existing pivot to ensure system is default
            $workspace->users()->updateExistingPivot($user->id, [
                'role' => 'owner',
                'is_default' => true,
            ]);
            // Clear other defaults for this user
            \Illuminate\Support\Facades\DB::table('user_workspace')
                ->where('user_id', $user->id)
                ->where('workspace_id', '!=', $workspace->id)
                ->update(['is_default' => false]);
        } else {
            $workspace->users()->attach($user->id, [
                'role' => 'owner',
                'is_default' => true,
            ]);
        }

        // Assign hermes package (founding patron - unlimited everything)
        $hermesPackage = Package::where('code', 'hermes')->first();
        if ($hermesPackage) {
            WorkspacePackage::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'package_id' => $hermesPackage->id,
                ],
                [
                    'status' => WorkspacePackage::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'expires_at' => null, // Never expires
                ]
            );
        }

        // Assign hades package (developer tools)
        $hadesPackage = Package::where('code', 'hades')->first();
        if ($hadesPackage) {
            WorkspacePackage::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'package_id' => $hadesPackage->id,
                ],
                [
                    'status' => WorkspacePackage::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'expires_at' => null, // Never expires
                ]
            );
        }

        // Assign apollo package (beta features)
        $apolloPackage = Package::where('code', 'apollo')->first();
        if ($apolloPackage) {
            WorkspacePackage::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'package_id' => $apolloPackage->id,
                ],
                [
                    'status' => WorkspacePackage::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'expires_at' => null, // Never expires
                ]
            );
        }

        $this->command->info("System user created: {$user->email} (ID: {$user->id})");
        $this->command->info("System workspace: {$workspace->name} with Hermes + Hades + Apollo packages");

        // Set up services for host.uk.com
        $this->setupServices($workspace, $user);
    }

    /**
     * Set up all services for the System workspace.
     */
    protected function setupServices(Workspace $workspace, User $user): void
    {
        // Environment-aware domains: .test for local, .uk.com for production
        $isLocal = app()->environment('local');
        $host = $isLocal ? 'host.test' : 'host.uk.com';
        $email = $isLocal ? 'support@host.test' : 'support@host.uk.com';

        // Analytics - website tracking
        $analyticsWebsite = AnalyticsWebsite::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'host' => $host,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Host UK',
                'url' => "https://{$host}",
                'pixel_key' => Str::uuid()->toString(),
                'tracking_enabled' => true,
                'is_enabled' => true,
                'track_pageviews' => true,
                'track_sessions' => true,
                'track_goals' => true,
            ]
        );
        $this->command->info("Analytics: {$analyticsWebsite->name} ({$analyticsWebsite->host})");

        // Trust - reviews campaign
        $trustCampaign = TrustCampaign::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'host' => $host,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Host UK Reviews',
                'pixel_key' => Str::uuid()->toString(),
                'is_enabled' => true,
                'primary_color' => '#8b5cf6', // Violet to match brand
            ]
        );
        $this->command->info("Trust: {$trustCampaign->name} ({$trustCampaign->host})");

        // Notify - push notifications
        $vapidKeys = PushWebsite::generateVapidKeys();
        $pushWebsite = PushWebsite::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'host' => $host,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Host UK',
                'pixel_key' => Str::uuid()->toString(),
                'vapid_public_key' => $vapidKeys['public'],
                'vapid_private_key' => $vapidKeys['private'],
                'is_enabled' => true,
                'widget_settings' => array_merge(
                    PushWebsite::defaultWidgetSettings(),
                    [
                        'primary_color' => '#8b5cf6',
                        'prompt_title' => 'Stay in the loop',
                        'prompt_message' => 'Get notified about new features and updates.',
                    ]
                ),
            ]
        );
        $this->command->info("Notify: {$pushWebsite->name} ({$pushWebsite->host})");

        // Support - mailbox
        $mailbox = Mailbox::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'email' => $email,
            ],
            [
                'name' => 'Support',
                'slug' => 'support',
                'signature' => "Best regards,\nThe Host UK Team",
                'auto_reply_enabled' => false,
            ]
        );
        $this->command->info("Support: {$mailbox->name} ({$mailbox->email})");

        // Bio - link in bio page
        $this->setupBioPage($workspace, $user);
    }

    /**
     * Set up the Host UK bio page.
     */
    protected function setupBioPage(Workspace $workspace, User $user): void
    {
        // Environment-aware domains
        $isLocal = app()->environment('local');
        $host = $isLocal ? 'host.test' : 'host.uk.com';
        $helpHost = $isLocal ? 'help.host.test' : 'help.host.uk.com';

        // Create the main bio page
        $bioPage = BioPage::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'url' => 'hostuk',
            ],
            [
                'user_id' => $user->id,
                'type' => 'biolink',
                'is_enabled' => true,
                'settings' => [
                    'seo' => [
                        'title' => 'Host UK - Creator Hosting Tools',
                        'description' => 'Premium hosting tools for UK creators. Bio pages, social scheduling, privacy-first analytics, and more.',
                    ],
                    'theme' => [
                        'background' => [
                            'type' => 'gradient',
                            'gradient_start' => '#1e1b4b',
                            'gradient_end' => '#312e81',
                            'gradient_direction' => '180',
                        ],
                        'text_color' => '#ffffff',
                        'font_family' => 'Inter',
                        'button' => [
                            'background_color' => '#8b5cf6',
                            'text_color' => '#ffffff',
                            'border_radius' => '12px',
                            'border_width' => '0',
                        ],
                    ],
                ],
            ]
        );

        // Create redirect from /host to /hostuk
        BioPage::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'url' => 'host',
            ],
            [
                'user_id' => $user->id,
                'type' => 'link',
                'location_url' => '/hostuk',
                'is_enabled' => true,
            ]
        );

        // Set up blocks (delete existing and recreate for clean state)
        $bioPage->blocks()->delete();

        $blocks = [
            [
                'type' => 'avatar',
                'order' => 1,
                'settings' => [
                    'image' => '/images/host-uk-logo-icon.png',
                    'size' => 'large',
                    'shape' => 'rounded',
                ],
            ],
            [
                'type' => 'heading',
                'order' => 2,
                'settings' => [
                    'text' => 'Host UK',
                    'size' => 'large',
                ],
            ],
            [
                'type' => 'paragraph',
                'order' => 3,
                'settings' => [
                    'text' => 'Premium hosting tools for UK creators and businesses. EU-hosted, GDPR compliant.',
                ],
            ],
            [
                'type' => 'socials',
                'order' => 4,
                'settings' => [
                    'style' => 'rounded',
                    'size' => 'medium',
                    'socials' => [
                        ['platform' => 'x', 'url' => 'https://x.com/hostukcom'],
                        ['platform' => 'github', 'url' => 'https://github.com/nicsnide'],
                        ['platform' => 'linkedin', 'url' => 'https://linkedin.com/company/hostukcom'],
                    ],
                ],
            ],
            [
                'type' => 'divider',
                'order' => 5,
                'settings' => ['style' => 'line'],
            ],
            [
                'type' => 'link',
                'order' => 6,
                'location_url' => "https://{$host}",
                'settings' => [
                    'name' => 'Visit Host UK',
                    'icon' => 'globe',
                ],
            ],
            [
                'type' => 'link',
                'order' => 7,
                'location_url' => "https://{$host}/pricing",
                'settings' => [
                    'name' => 'View Pricing',
                    'icon' => 'tag',
                ],
            ],
            [
                'type' => 'link',
                'order' => 8,
                'location_url' => "https://{$helpHost}",
                'settings' => [
                    'name' => 'Help Centre',
                    'icon' => 'book-open',
                ],
            ],
            [
                'type' => 'link',
                'order' => 9,
                'location_url' => "mailto:hello@{$host}",
                'settings' => [
                    'name' => 'Get in Touch',
                    'icon' => 'envelope',
                ],
            ],
        ];

        foreach ($blocks as $blockData) {
            Block::create([
                'workspace_id' => $workspace->id,
                'biolink_id' => $bioPage->id,
                'type' => $blockData['type'],
                'order' => $blockData['order'],
                'location_url' => $blockData['location_url'] ?? null,
                'settings' => $blockData['settings'],
                'is_enabled' => true,
            ]);
        }

        $this->command->info("Bio: /{$bioPage->url} (with ".count($blocks).' blocks)');
        $this->command->info('Bio: /host -> /hostuk (redirect)');
    }
}
