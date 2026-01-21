<?php

namespace Core\Mod\Mcp\Servers;

use Core\Mod\Mcp\Tools\AnalyticsTools;
use Core\Mod\Mcp\Tools\PushNotificationTools;
use Laravel\Mcp\Server;

/**
 * Marketing MCP Server.
 *
 * Provides a unified interface for MCP agents to interact with
 * Host UK's marketing platform:
 * - BioHost (bio link pages)
 * - AnalyticsHost (website analytics)
 * - NotifyHost (push notifications)
 * - TrustHost (social proof widgets)
 */
class Marketing extends Server
{
    protected string $name = 'Host UK Marketing';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        Host UK Marketing MCP Server provides tools for managing the complete marketing platform.

        ## Available Tools

        ### BioLink Tools (BioHost)
        Manage bio link pages, domains, pixels, themes, and notifications:

        #### Core Operations (biolink_tools)
        - `list` - List all bio links
        - `get` - Get bio link details with blocks
        - `create` - Create a new bio link page
        - `add_block` - Add a content block
        - `update_block` - Update block settings
        - `delete_block` - Remove a block

        #### Analytics (analytics_tools)
        - `stats` - Get click statistics
        - `detailed` - Get detailed breakdown

        #### Domains (domain_tools)
        - `list` - List custom domains
        - `add` - Add domain
        - `verify` - Verify DNS

        #### Themes (theme_tools)
        - `list` - List themes
        - `apply` - Apply theme

        #### Other Bio Tools
        - `qr_tools` - Generate QR codes
        - `pixel_tools` - Manage tracking pixels
        - `project_tools` - Organize into projects
        - `notification_tools` - Manage notification handlers
        - `submission_tools` - Manage form submissions
        - `pwa_tools` - Configure PWA

        ### AnalyticsTools
        Query website analytics data:
        - `list_websites` - List tracked websites
        - `get_stats` - Get pageviews, visitors, bounce rate
        - `top_pages` - Get most visited pages
        - `traffic_sources` - Get referrers and UTM campaigns
        - `realtime` - Get current active visitors

        ### PushNotificationTools
        Manage push notification campaigns:
        - `list_websites` - List push-enabled websites
        - `list_campaigns` - List notification campaigns
        - `get_campaign` - Get campaign details and stats
        - `create_campaign` - Create a new campaign (as draft)
        - `subscriber_stats` - Get subscriber demographics

        ### Social Proof (TrustHost - trust_tools)
        Manage social proof widgets and campaigns:
        - `trust_campaign_tools` action=list: List campaigns
        - `trust_notification_tools` action=list: List widgets
        - `trust_analytics_tools` action=stats: Get performance stats

        ### AnalyticsTools
        Query website analytics data:
    MARKDOWN;

    protected array $tools = [
        // BioHost tools
        \Core\Mod\Web\Mcp\Tools\BioLinkTools::class,
        \Core\Mod\Web\Mcp\Tools\AnalyticsTools::class,
        \Core\Mod\Web\Mcp\Tools\DomainTools::class,
        \Core\Mod\Web\Mcp\Tools\ProjectTools::class,
        \Core\Mod\Web\Mcp\Tools\PixelTools::class,
        \Core\Mod\Web\Mcp\Tools\QrTools::class,
        \Core\Mod\Web\Mcp\Tools\ThemeTools::class,
        \Core\Mod\Web\Mcp\Tools\NotificationTools::class,
        \Core\Mod\Web\Mcp\Tools\SubmissionTools::class,
        \Core\Mod\Web\Mcp\Tools\TemplateTools::class,
        \Core\Mod\Web\Mcp\Tools\StaticPageTools::class,
        \Core\Mod\Web\Mcp\Tools\PwaTools::class,

        // Other Marketing tools
        AnalyticsTools::class,
        PushNotificationTools::class,
        \Core\Mod\Trust\Mcp\Tools\CampaignTools::class,
        \Core\Mod\Trust\Mcp\Tools\NotificationTools::class,
        \Core\Mod\Trust\Mcp\Tools\AnalyticsTools::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
