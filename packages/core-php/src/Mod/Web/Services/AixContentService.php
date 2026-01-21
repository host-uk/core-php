<?php

declare(strict_types=1);

namespace Core\Mod\Web\Services;

use Core\Mod\Agentic\Services\AgenticManager;
use Core\Mod\Agentic\Services\AgenticResponse;
use Core\Mod\Web\Models\Page;
use Illuminate\Support\Str;

/**
 * AI content generation service for BioHost.
 *
 * Provides AI-powered content generation for biolink pages including
 * bio descriptions, link text, SEO content, and improvement suggestions.
 */
class AixContentService
{
    public function __construct(
        protected AgenticManager $agenticManager
    ) {}

    /**
     * Generate a bio description from a simple user input.
     *
     * @param  string  $description  User's description or keywords
     * @param  string|null  $style  Optional style (professional, casual, creative)
     * @return string Generated bio text
     */
    public function generateBio(string $description, ?string $style = null): string
    {
        $styleGuide = match ($style) {
            'professional' => 'Write in a professional, business-appropriate tone.',
            'casual' => 'Write in a friendly, conversational tone.',
            'creative' => 'Write in a creative, unique tone with personality.',
            default => 'Write in a clear, engaging tone.',
        };

        $systemPrompt = <<<SYSTEM
You are a professional copywriter specialising in bio page content.
Your task is to write engaging, concise bio descriptions for online profiles.

Guidelines:
- Use UK English spelling (colour, organisation, centre, etc.)
- Keep it concise (50-150 words)
- Make it engaging and authentic
- Avoid buzzwords like "leverage", "utilise", "synergy", "cutting-edge"
- Use active voice
- {$styleGuide}
SYSTEM;

        $userPrompt = <<<USER
Write a bio description based on this information:

{$description}

Return ONLY the bio text, no explanation or additional commentary.
USER;

        $response = $this->agenticManager->provider()->generate(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            config: ['max_tokens' => 500]
        );

        return trim($response->content);
    }

    /**
     * Generate descriptive text for a link based on URL and context.
     *
     * @param  string  $url  The URL to generate text for
     * @param  string|null  $context  Optional context about the link
     * @return string Generated link text
     */
    public function generateLinkText(string $url, ?string $context = null): string
    {
        $systemPrompt = <<<'SYSTEM'
You are a copywriter creating clear, actionable link text.
Your task is to write concise, descriptive text for links.

Guidelines:
- Use UK English spelling
- Keep it short (2-8 words)
- Make it action-oriented when appropriate
- Be clear and descriptive
- Avoid generic text like "Click here"
SYSTEM;

        $contextText = $context ? "\n\nContext: {$context}" : '';

        $userPrompt = <<<USER
Create link text for this URL:

{$url}{$contextText}

Return ONLY the link text, no explanation.
USER;

        $response = $this->agenticManager->provider()->generate(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            config: ['max_tokens' => 100]
        );

        return trim($response->content);
    }

    /**
     * Suggest improvements for a biolink page.
     *
     * @param  Page  $biolink  The biolink to analyse
     * @return array Array of suggestions with categories
     */
    public function suggestImprovements(Page $biolink): array
    {
        $systemPrompt = <<<'SYSTEM'
You are a conversion optimisation expert reviewing bio pages.
Provide actionable suggestions to improve the page's effectiveness.

Guidelines:
- Use UK English spelling
- Be specific and actionable
- Focus on content, structure, and user experience
- Categorise suggestions (content, design, seo, engagement)
SYSTEM;

        // Build context about the biolink
        $blocks = $biolink->blocks;
        $seoTitle = $biolink->getSeoTitle();
        $seoDescription = $biolink->getSeoDescription();

        $blocksSummary = $blocks->map(function ($block) {
            $text = $block->settings['text'] ?? $block->settings['label'] ?? 'N/A';

            return "- {$block->type}: {$text}";
        })->implode("\n");

        $userPrompt = <<<USER
Analyse this bio page and provide 3-5 specific improvement suggestions:

SEO Title: {$seoTitle}
SEO Description: {$seoDescription}
Number of blocks: {$blocks->count()}

Blocks:
{$blocksSummary}

Return suggestions as JSON array with this format:
[
    {
        "category": "content|design|seo|engagement",
        "priority": "high|medium|low",
        "suggestion": "Specific actionable suggestion"
    }
]

Return ONLY the JSON array, no explanation.
USER;

        $response = $this->agenticManager->provider()->generate(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            config: ['max_tokens' => 800]
        );

        try {
            $suggestions = json_decode($response->content, true, 512, JSON_THROW_ON_ERROR);

            return is_array($suggestions) ? $suggestions : [];
        } catch (\JsonException $e) {
            // If JSON parsing fails, return a basic suggestion
            return [[
                'category' => 'general',
                'priority' => 'medium',
                'suggestion' => 'Review your page content and ensure it clearly communicates your value proposition.',
            ]];
        }
    }

    /**
     * Generate social media description for a bio.
     *
     * @param  Page  $biolink  The biolink to generate description for
     * @param  string  $platform  Social platform (twitter, facebook, linkedin)
     * @return string Generated description
     */
    public function generateSocialDescription(Page $biolink, string $platform = 'general'): string
    {
        $platformGuide = match ($platform) {
            'twitter' => 'Keep it under 280 characters. Make it punchy and engaging.',
            'facebook' => 'Write 1-2 sentences. Make it conversational and engaging.',
            'linkedin' => 'Write in a professional tone. 1-2 sentences.',
            default => 'Write 1-2 engaging sentences suitable for social sharing.',
        };

        $systemPrompt = <<<SYSTEM
You are a social media copywriter creating share descriptions.

Guidelines:
- Use UK English spelling
- {$platformGuide}
- Include a call to action when appropriate
- Make it engaging and shareable
- Avoid buzzwords
SYSTEM;

        $seoTitle = $biolink->getSeoTitle() ?? $biolink->url;
        $seoDescription = $biolink->getSeoDescription() ?? '';

        $userPrompt = <<<USER
Create a social media description for this bio page:

Title: {$seoTitle}
Description: {$seoDescription}

Return ONLY the social description, no explanation.
USER;

        $response = $this->agenticManager->provider()->generate(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            config: ['max_tokens' => 200]
        );

        return trim($response->content);
    }

    /**
     * Generate SEO meta title for a bio.
     *
     * @param  Page  $biolink  The biolink to generate title for
     * @return string Generated title (max 60 chars)
     */
    public function generateSeoTitle(Page $biolink): string
    {
        $systemPrompt = <<<'SYSTEM'
You are an SEO expert creating meta titles.

Guidelines:
- Use UK English spelling
- Maximum 60 characters
- Include primary keywords
- Make it compelling for search results
- No quotation marks
SYSTEM;

        $blocks = $biolink->blocks;
        $blocksSummary = $blocks->take(3)->map(fn ($block) => $block->type)->implode(', ');

        $userPrompt = <<<USER
Create an SEO meta title for a bio page with these blocks: {$blocksSummary}

Return ONLY the title text, no quotation marks or explanation.
USER;

        $response = $this->agenticManager->provider()->generate(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            config: ['max_tokens' => 50]
        );

        return Str::limit(trim($response->content), 60, '');
    }

    /**
     * Generate SEO meta description for a bio.
     *
     * @param  Page  $biolink  The biolink to generate description for
     * @return string Generated description (max 160 chars)
     */
    public function generateSeoDescription(Page $biolink): string
    {
        $systemPrompt = <<<'SYSTEM'
You are an SEO expert creating meta descriptions.

Guidelines:
- Use UK English spelling
- Maximum 160 characters
- Include a call to action
- Make it compelling for search results
- No quotation marks
SYSTEM;

        $blocks = $biolink->blocks;
        $blocksSummary = $blocks->take(5)->map(function ($block) {
            return $block->settings['text'] ?? $block->settings['label'] ?? $block->type;
        })->implode(', ');

        $userPrompt = <<<USER
Create an SEO meta description for a bio page featuring: {$blocksSummary}

Return ONLY the description text, no quotation marks or explanation.
USER;

        $response = $this->agenticManager->provider()->generate(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
            config: ['max_tokens' => 100]
        );

        return Str::limit(trim($response->content), 160, '');
    }

    /**
     * Get the last AI response for debugging/logging.
     */
    public function getLastResponse(): ?AgenticResponse
    {
        // This could be expanded to store the last response
        // For now, return null as responses are not cached
        return null;
    }
}
