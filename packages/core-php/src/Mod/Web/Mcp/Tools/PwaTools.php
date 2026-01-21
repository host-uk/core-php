<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pwa;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class PwaTools extends BaseBioTool
{
    protected string $name = 'pwa_tools';

    protected string $description = 'Configure Progressive Web App (PWA) settings for bio pages';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');

        return match ($action) {
            'configure' => $this->configurePwa($request),
            'get_config' => $this->getPwaConfig($request->get('biolink_id')),
            'generate_manifest' => $this->generateManifest($request->get('biolink_id')),
            default => $this->error('Invalid action', ['available' => ['configure', 'get_config', 'generate_manifest']]),
        };
    }

    protected function configurePwa(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');

        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $config = $request->get('config', []);

        if (empty($config)) {
            return $this->error('config is required');
        }

        if (empty($config['name'])) {
            return $this->error('config.name is required');
        }

        $pwaData = [
            'biolink_id' => $biolinkId,
            'name' => $config['name'],
            'short_name' => $config['short_name'] ?? null,
            'description' => $config['description'] ?? null,
            'theme_color' => $config['theme_color'] ?? '#6366f1',
            'background_color' => $config['background_color'] ?? '#ffffff',
            'display' => $config['display'] ?? Pwa::DISPLAY_STANDALONE,
            'orientation' => $config['orientation'] ?? 'any',
            'lang' => $config['lang'] ?? 'en-GB',
            'dir' => $config['dir'] ?? 'auto',
            'icon_url' => $config['icon_url'] ?? null,
            'icon_maskable_url' => $config['icon_maskable_url'] ?? null,
            'screenshots' => $config['screenshots'] ?? [],
            'shortcuts' => $config['shortcuts'] ?? [],
            'is_enabled' => $config['is_enabled'] ?? true,
        ];

        if ($biolink->pwa) {
            $biolink->pwa->update($pwaData);
            $pwa = $biolink->pwa;
        } else {
            $pwa = Pwa::create($pwaData);
        }

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolinkId,
            'pwa_id' => $pwa->id,
            'config' => $pwaData,
        ]);
    }

    protected function getPwaConfig(?int $biolinkId): Response
    {
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::with('pwa')->find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        if (! $biolink->pwa) {
            return $this->json([
                'pwa_configured' => false,
                'config' => null,
            ]);
        }

        return $this->json([
            'pwa_configured' => true,
            'biolink_id' => $biolinkId,
            'config' => $biolink->pwa->toArray(),
        ]);
    }

    protected function generateManifest(?int $biolinkId): Response
    {
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::with('pwa')->find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        if (! $biolink->pwa || ! $biolink->pwa->is_enabled) {
            return $this->error('PWA is disabled for this biolink');
        }

        return $this->json([
            'manifest' => $biolink->pwa->toManifest(),
        ]);
    }
}
