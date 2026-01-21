<?php

namespace Core\Mod\Web\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class StaticPageSanitiser
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        // Allow a comprehensive set of HTML5 elements
        $config->set('HTML.Allowed', implode(',', [
            // Structure
            'div[id|class|style]',
            'span[id|class|style]',
            'section[id|class|style]',
            'article[id|class|style]',
            'header[id|class|style]',
            'footer[id|class|style]',
            'main[id|class|style]',
            'nav[id|class|style]',
            'aside[id|class|style]',

            // Text
            'h1[id|class|style]',
            'h2[id|class|style]',
            'h3[id|class|style]',
            'h4[id|class|style]',
            'h5[id|class|style]',
            'h6[id|class|style]',
            'p[id|class|style]',
            'br',
            'hr[id|class|style]',
            'strong',
            'em',
            'b',
            'i',
            'u',
            'small',
            'mark',
            'del',
            'ins',
            'sub',
            'sup',
            'code',
            'pre[id|class|style]',
            'blockquote[id|class|style]',

            // Lists
            'ul[id|class|style]',
            'ol[id|class|style]',
            'li[id|class|style]',
            'dl[id|class|style]',
            'dt[id|class|style]',
            'dd[id|class|style]',

            // Links and media
            'a[href|id|class|style|target|rel]',
            'img[src|alt|width|height|id|class|style]',
            'picture[id|class|style]',
            'source[src|srcset|type|media]',
            'video[src|controls|width|height|poster|preload|id|class|style]',
            'audio[src|controls|preload|id|class|style]',
            'iframe[src|width|height|frameborder|allowfullscreen|id|class|style]',

            // Tables
            'table[id|class|style]',
            'thead[id|class|style]',
            'tbody[id|class|style]',
            'tfoot[id|class|style]',
            'tr[id|class|style]',
            'th[id|class|style|colspan|rowspan]',
            'td[id|class|style|colspan|rowspan]',
            'caption[id|class|style]',

            // Forms (for lead capture)
            'form[id|class|style|action|method]',
            'input[type|name|id|class|style|placeholder|value|required]',
            'textarea[name|id|class|style|placeholder|rows|cols|required]',
            'button[type|id|class|style]',
            'label[for|id|class|style]',
            'select[name|id|class|style|required]',
            'option[value|selected]',
            'fieldset[id|class|style]',
            'legend[id|class|style]',

            // Note: details/summary not fully supported by HTMLPurifier yet
        ]));

        // Allow inline styles (scoped separately by CssScopeService)
        $config->set('CSS.AllowTricky', true);
        $config->set('CSS.Trusted', true);

        // Allow common attributes (note: data-* needs custom implementation)
        // For now, we allow specific common attributes
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);

        // Disable cache for now (can enable for production)
        $config->set('Cache.DefinitionImpl', null);

        // Safe links only
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'tel' => true,
        ]);

        // Allow iframes from trusted sources
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube\.com/embed/|player\.vimeo\.com/video/)%');

        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * Sanitise HTML content to prevent XSS attacks.
     */
    public function sanitiseHtml(string $html): string
    {
        // Suppress HTMLPurifier warnings about unsupported elements
        // These are safe to ignore as they just mean some advanced HTML5 elements
        // won't be allowed through (which is fine for security)
        return @$this->purifier->purify($html);
    }

    /**
     * Sanitise JavaScript content.
     *
     * This is a basic sanitiser that strips obvious XSS patterns.
     * For production, consider using a more robust JS parser.
     */
    public function sanitiseJavaScript(string $js): string
    {
        // Remove any script tags that might be embedded
        $js = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $js);

        // Remove eval() calls (potential XSS vector)
        $js = preg_replace('/eval\s*\(/i', '/* eval blocked */ (', $js);

        // Remove document.write (potential XSS vector)
        $js = preg_replace('/document\.write\s*\(/i', '/* document.write blocked */ (', $js);

        return $js;
    }

    /**
     * Validate and clean CSS content.
     *
     * This is a basic sanitiser. CSS scoping is handled by CssScopeService.
     */
    public function sanitiseCss(string $css): string
    {
        // Remove any script tags or expressions
        $css = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $css);
        $css = preg_replace('/expression\s*\(/i', '/* expression blocked */', $css);
        $css = preg_replace('/javascript:/i', '/* protocol blocked */', $css);
        $css = preg_replace('/@import\s+[^;]+;/i', '/* import blocked */', $css);
        $css = preg_replace('/@import\s+url\([^)]+\);?/i', '/* import blocked */', $css);

        return $css;
    }

    /**
     * Sanitise all content for a static page.
     */
    public function sanitiseStaticPage(string $html, string $css = '', string $js = ''): array
    {
        return [
            'html' => $this->sanitiseHtml($html),
            'css' => $this->sanitiseCss($css),
            'js' => $this->sanitiseJavaScript($js),
        ];
    }
}
