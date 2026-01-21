<?php

declare(strict_types=1);

namespace Core\Mod\Web\Actions;

use Illuminate\Support\Str;
use Core\Mod\Analytics\Enums\ChannelType;
use Core\Mod\Analytics\Events\ProvisionChannel;
use Core\Mod\Tenant\Exceptions\EntitlementException;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Core\Mod\Web\Models\Page;
use Spatie\Activitylog\Facades\Activity;

/**
 * Create a new biolink with entitlement checking.
 *
 * Usage:
 *   $action = app(CreateBiolink::class);
 *   $biolink = $action->handle($user, $data);
 *
 *   // Or via static helper:
 *   $biolink = CreateBiolink::run($user, $data);
 */
class CreateBiolink
{
    public function __construct(
        protected EntitlementService $entitlements
    ) {}

    /**
     * Create a new bio.
     *
     * @param  array{url?: string, type?: string, settings?: array, project_id?: int, workspace_id?: int}  $data
     *
     * @throws EntitlementException
     */
    public function handle(User $user, array $data): Page
    {
        $workspace = $user->defaultHostWorkspace();
        $type = $data['type'] ?? 'biolink';

        // Check entitlements if workspace exists
        if ($workspace) {
            $this->checkEntitlements($workspace, $type);
        }

        // Generate unique URL if not provided
        $data['url'] = $data['url'] ?? $this->generateUniqueUrl();

        // Ensure user_id and workspace_id are set
        $data['user_id'] = $user->id;
        $data['workspace_id'] = $data['workspace_id'] ?? $workspace?->id;

        // Set defaults
        $data['type'] = $type;
        $data['is_enabled'] = $data['is_enabled'] ?? true;
        $data['is_verified'] = $data['is_verified'] ?? false;
        $data['settings'] = $data['settings'] ?? $this->defaultSettings();

        // Create the biolink
        $biolink = Page::create($data);

        // Record usage if workspace exists
        if ($workspace) {
            $featureCode = $this->getFeatureCodeForType($type);
            $this->entitlements->recordUsage(
                $workspace,
                $featureCode,
                1,
                $user,
                ['biolink_id' => $biolink->id, 'type' => $type]
            );
        }

        // Log activity
        Activity::causedBy($user)
            ->performedOn($biolink)
            ->withProperties([
                'url' => $biolink->url,
                'type' => $biolink->type,
            ])
            ->log('created');

        // Provision analytics channel for biolink pages
        if ($biolink->isBioLinkPage() && $workspace) {
            $this->provisionAnalyticsChannel($biolink, $workspace, $user);
        }

        return $biolink;
    }

    /**
     * Static helper to run the action.
     */
    public static function run(User $user, array $data): Page
    {
        return app(static::class)->handle($user, $data);
    }

    /**
     * Check entitlements for creating a bio.
     *
     * @throws EntitlementException
     */
    protected function checkEntitlements(Workspace $workspace, string $type): void
    {
        $featureCode = $this->getFeatureCodeForType($type);
        $result = $this->entitlements->can($workspace, $featureCode);

        if ($result->isDenied()) {
            $limitName = $type === 'biolink' ? 'bio page' : 'short link';
            throw new EntitlementException(
                "You have reached your {$limitName} limit. Please upgrade your plan.",
                $featureCode
            );
        }
    }

    /**
     * Get the feature code for a biolink type.
     */
    protected function getFeatureCodeForType(string $type): string
    {
        return match ($type) {
            'biolink' => 'bio.pages',
            default => 'bio.shortlinks', // link, file, vcard, event all count as shortlinks
        };
    }

    /**
     * Generate a unique URL slug for the bio.
     */
    protected function generateUniqueUrl(): string
    {
        do {
            $url = Str::random(8);
        } while (Page::where('url', $url)->exists());

        return $url;
    }

    /**
     * Get default settings for a new bio.
     */
    protected function defaultSettings(): array
    {
        return [
            'title' => '',
            'description' => '',
            'theme' => 'default',
            'background' => [
                'type' => 'color',
                'value' => '#ffffff',
            ],
            'font' => 'inter',
            'button_style' => 'rounded',
            'social_icons_position' => 'bottom',
            'seo' => [
                'title' => '',
                'description' => '',
            ],
        ];
    }

    /**
     * Provision an analytics channel for a biolink.
     */
    protected function provisionAnalyticsChannel(Page $biolink, Workspace $workspace, User $user): void
    {
        // Build the host path - use domain if set, otherwise default lt.hn
        $host = $biolink->domain
            ? $biolink->domain->host.'/'.$biolink->url
            : 'lt.hn/'.$biolink->url;

        ProvisionChannel::dispatch(
            workspaceId: $workspace->id,
            channelType: ChannelType::Bio,
            name: $biolink->getSetting('title') ?: $biolink->url,
            host: $host,
            userId: $user->id,
            metadata: ['biolink_id' => $biolink->id],
        );
    }
}
