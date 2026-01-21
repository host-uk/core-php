<?php

declare(strict_types=1);

namespace Core\Mod\Web\Services;

use Core\Front\Components\Layout;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * HLCRF Renderer - Renders bio pages with multi-region layouts.
 *
 * Takes a Page with layout_config and renders blocks into their
 * assigned regions using the Core Layout compositor.
 */
class HlcrfRenderer
{
    public function __construct(
        protected BlockConditionService $conditionService
    ) {}

    /**
     * Render a page with HLCRF layout.
     *
     * @param Page $page The bio page to render
     * @param Request $request Current request for condition evaluation
     * @param string $breakpoint The breakpoint to render for (phone, tablet, desktop)
     */
    public function render(Page $page, Request $request, string $breakpoint = 'desktop'): string
    {
        // Get the layout type for this breakpoint
        $layoutType = $page->getLayoutTypeFor($breakpoint);

        // Group blocks by region, filtered by conditions
        $blocksByRegion = $this->getVisibleBlocksByRegion($page, $request);

        // Build the layout using the Core compositor
        $layout = Layout::make($layoutType)
            ->class('bio-hlcrf min-h-screen');

        // Populate each region
        if (str_contains($layoutType, 'H')) {
            $this->addRegionContent($layout, 'h', $blocksByRegion['header'], 'H');
        }

        if (str_contains($layoutType, 'L')) {
            $this->addRegionContent($layout, 'l', $blocksByRegion['left'], 'L');
        }

        if (str_contains($layoutType, 'C')) {
            $this->addRegionContent($layout, 'c', $blocksByRegion['content'], 'C');
        }

        if (str_contains($layoutType, 'R')) {
            $this->addRegionContent($layout, 'r', $blocksByRegion['right'], 'R');
        }

        if (str_contains($layoutType, 'F')) {
            $this->addRegionContent($layout, 'f', $blocksByRegion['footer'], 'F');
        }

        return $layout->render();
    }

    /**
     * Get visible blocks grouped by region.
     *
     * @return array<string, Collection>
     */
    protected function getVisibleBlocksByRegion(Page $page, Request $request): array
    {
        $allBlocks = $page->blocks;

        // Filter by visibility conditions
        $visibleBlocks = $allBlocks->filter(
            fn (Block $block) => $this->conditionService->shouldDisplay($block, $request)
        );

        return [
            'header' => $visibleBlocks->where('region', Block::REGION_HEADER)->sortBy('region_order')->values(),
            'left' => $visibleBlocks->where('region', Block::REGION_LEFT)->sortBy('region_order')->values(),
            'content' => $visibleBlocks->where('region', Block::REGION_CONTENT)->sortBy('region_order')->values(),
            'right' => $visibleBlocks->where('region', Block::REGION_RIGHT)->sortBy('region_order')->values(),
            'footer' => $visibleBlocks->where('region', Block::REGION_FOOTER)->sortBy('region_order')->values(),
        ];
    }

    /**
     * Add blocks to a layout region.
     *
     * @param Layout $layout The layout compositor
     * @param string $method The layout method (h, l, c, r, f)
     * @param Collection $blocks Blocks for this region
     * @param string $regionCode The region short code (H, L, C, R, F)
     */
    protected function addRegionContent(Layout $layout, string $method, Collection $blocks, string $regionCode): void
    {
        foreach ($blocks as $block) {
            $rendered = $this->renderBlockForRegion($block, $regionCode);
            $layout->$method($rendered);
        }
    }

    /**
     * Render a block with region context.
     *
     * Checks for region-specific templates first, falls back to default.
     */
    protected function renderBlockForRegion(Block $block, string $regionCode): string
    {
        // Try region-specific template first
        $regionView = "lthn::bio.blocks.{$block->type}.{$regionCode}";
        if (view()->exists($regionView)) {
            return view($regionView, [
                'block' => $block,
                'settings' => $block->settings ?? new \ArrayObject,
                'region' => $regionCode,
            ])->render();
        }

        // Fall back to default block rendering with region context
        $defaultView = "lthn::bio.blocks.{$block->type}";
        if (view()->exists($defaultView)) {
            return view($defaultView, [
                'block' => $block,
                'settings' => $block->settings ?? new \ArrayObject,
                'region' => $regionCode,
            ])->render();
        }

        // Generic fallback
        return view('lthn::bio.blocks.generic', [
            'block' => $block,
            'settings' => $block->settings ?? new \ArrayObject,
            'region' => $regionCode,
        ])->render();
    }

    /**
     * Check if a page should use HLCRF rendering.
     */
    public function shouldUseHlcrf(Page $page): bool
    {
        return $page->hasHlcrfLayout();
    }

    /**
     * Detect the appropriate breakpoint from request.
     */
    public function detectBreakpoint(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');

        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $ua)) {
            return 'phone';
        }

        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Get CSS variables for theming.
     */
    public function getThemeCss(Page $page): string
    {
        $themeSettings = $page->getThemeSettings();
        $bg = $page->getBackground();
        $buttonStyle = $page->getButtonStyle();
        $fontFamily = $page->getFontFamily();
        $textColor = $page->getTextColor();

        $css = ":root {\n";
        $css .= "    --biolink-bg: " . ($bg['color'] ?? '#f9fafb') . ";\n";
        $css .= "    --biolink-bg-gradient-start: " . ($bg['gradient_start'] ?? $bg['color'] ?? '#f9fafb') . ";\n";
        $css .= "    --biolink-bg-gradient-end: " . ($bg['gradient_end'] ?? $bg['color'] ?? '#f9fafb') . ";\n";
        $css .= "    --biolink-text: {$textColor};\n";
        $css .= "    --biolink-btn-bg: " . ($buttonStyle['background_color'] ?? '#000000') . ";\n";
        $css .= "    --biolink-btn-text: " . ($buttonStyle['text_color'] ?? '#ffffff') . ";\n";
        $css .= "    --biolink-btn-radius: " . ($buttonStyle['border_radius'] ?? '8px') . ";\n";
        $css .= "    --biolink-btn-border-width: " . ($buttonStyle['border_width'] ?? '0') . ";\n";
        $css .= "    --biolink-btn-border-color: " . ($buttonStyle['border_color'] ?? 'transparent') . ";\n";
        $css .= "    --biolink-font: '{$fontFamily}', system-ui, -apple-system, sans-serif;\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Get layout CSS for HLCRF grid.
     */
    public function getLayoutCss(Page $page): string
    {
        $config = $page->layout_config ?? [];
        $regions = $config['regions']['desktop'] ?? [];

        $leftWidth = $regions['left']['width'] ?? 280;
        $rightWidth = $regions['right']['width'] ?? 280;
        $contentMaxWidth = $regions['content']['max_width'] ?? 680;

        return <<<CSS
.bio-hlcrf {
    display: grid;
    grid-template-rows: auto 1fr auto;
    grid-template-areas:
        "header"
        "body"
        "footer";
}
.hlcrf-header { grid-area: header; }
.hlcrf-body {
    grid-area: body;
    display: grid;
    grid-template-columns: {$leftWidth}px minmax(0, {$contentMaxWidth}px) {$rightWidth}px;
    grid-template-areas: "left content right";
    justify-content: center;
    gap: 2rem;
}
.hlcrf-left { grid-area: left; }
.hlcrf-content { grid-area: content; }
.hlcrf-right { grid-area: right; }
.hlcrf-footer { grid-area: footer; }

/* Tablet: Hide sidebars */
@media (max-width: 1023px) {
    .hlcrf-body {
        grid-template-columns: minmax(0, {$contentMaxWidth}px);
        grid-template-areas: "content";
    }
    .hlcrf-left, .hlcrf-right { display: none; }
}

/* Phone: Stack everything */
@media (max-width: 767px) {
    .hlcrf-body {
        grid-template-columns: 1fr;
        padding: 0 1rem;
    }
}
CSS;
    }
}
