<?php

namespace Core\Mod\Web\Services;

class CssScopeService
{
    /**
     * Scope CSS rules to a specific container to prevent bleeding.
     *
     * This prefixes all CSS selectors with a unique container ID to ensure
     * styles only apply within the static page content area.
     *
     * @param string $css The CSS content to scope
     * @param string $scopeId The unique ID to scope to (e.g., 'static-page-123')
     * @return string Scoped CSS
     */
    public function scopeCss(string $css, string $scopeId): string
    {
        // Remove comments first
        $css = $this->removeComments($css);

        // Split CSS into individual rules
        $rules = $this->parseRules($css);

        $scopedRules = [];

        foreach ($rules as $rule) {
            if ($this->isAtRule($rule)) {
                // Handle @media, @keyframes, @supports, etc.
                $scopedRules[] = $this->scopeAtRule($rule, $scopeId);
            } else {
                // Regular CSS rule
                $scopedRules[] = $this->scopeRegularRule($rule, $scopeId);
            }
        }

        return implode("\n\n", $scopedRules);
    }

    /**
     * Remove CSS comments.
     */
    private function removeComments(string $css): string
    {
        return preg_replace('/\/\*.*?\*\//s', '', $css);
    }

    /**
     * Parse CSS into individual rules.
     */
    private function parseRules(string $css): array
    {
        $rules = [];
        $depth = 0;
        $currentRule = '';

        $chars = str_split($css);
        $length = count($chars);

        for ($i = 0; $i < $length; $i++) {
            $char = $chars[$i];
            $currentRule .= $char;

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0 && trim($currentRule)) {
                    $rules[] = trim($currentRule);
                    $currentRule = '';
                }
            }
        }

        // Handle any remaining content
        if (trim($currentRule)) {
            $rules[] = trim($currentRule);
        }

        return array_filter($rules);
    }

    /**
     * Check if a rule is an at-rule (@media, @keyframes, etc.).
     */
    private function isAtRule(string $rule): bool
    {
        return preg_match('/^\s*@/', $rule);
    }

    /**
     * Scope an at-rule (like @media or @supports).
     */
    private function scopeAtRule(string $rule, string $scopeId): string
    {
        // Extract the at-rule condition and body
        if (preg_match('/^(\s*@[^{]+)\{(.+)\}$/s', $rule, $matches)) {
            $condition = $matches[1];
            $body = $matches[2];

            // Don't scope @keyframes - they're global by nature
            if (preg_match('/@keyframes/i', $condition)) {
                return $rule;
            }

            // Parse and scope rules inside the at-rule
            $innerRules = $this->parseRules($body);
            $scopedInner = [];

            foreach ($innerRules as $innerRule) {
                if ($this->isAtRule($innerRule)) {
                    $scopedInner[] = $this->scopeAtRule($innerRule, $scopeId);
                } else {
                    $scopedInner[] = $this->scopeRegularRule($innerRule, $scopeId);
                }
            }

            return $condition . ' {' . "\n" . implode("\n", $scopedInner) . "\n" . '}';
        }

        return $rule;
    }

    /**
     * Scope a regular CSS rule.
     */
    private function scopeRegularRule(string $rule, string $scopeId): string
    {
        // Split selector and declarations
        if (preg_match('/^([^{]+)\{([^}]*)\}$/s', $rule, $matches)) {
            $selectors = $matches[1];
            $declarations = $matches[2];

            // Split multiple selectors
            $selectorList = array_map('trim', explode(',', $selectors));
            $scopedSelectors = [];

            foreach ($selectorList as $selector) {
                $scopedSelectors[] = $this->scopeSelector($selector, $scopeId);
            }

            return implode(', ', $scopedSelectors) . ' {' . $declarations . '}';
        }

        return $rule;
    }

    /**
     * Scope a single CSS selector.
     */
    private function scopeSelector(string $selector, string $scopeId): string
    {
        $selector = trim($selector);

        // Don't scope :root or html/body (these affect global elements)
        if (preg_match('/^(:root|html|body)(\s|$)/i', $selector)) {
            // Replace html/body with the scope ID
            if (preg_match('/^(html|body)/', $selector, $matches)) {
                return '#' . $scopeId . preg_replace('/^(html|body)/', '', $selector);
            }

            return '#' . $scopeId;
        }

        // Don't scope pseudo-elements that are already scoped
        if (strpos($selector, '#' . $scopeId) === 0) {
            return $selector;
        }

        // Prefix the selector with the scope ID
        return '#' . $scopeId . ' ' . $selector;
    }

    /**
     * Generate a unique scope ID for a bio.
     */
    public function generateScopeId(int $biolinkId): string
    {
        return 'static-page-' . $biolinkId;
    }

    /**
     * Wrap HTML content in a scoped container div.
     */
    public function wrapInScope(string $html, string $scopeId): string
    {
        return sprintf('<div id="%s" class="static-page-content">%s</div>', $scopeId, $html);
    }
}
