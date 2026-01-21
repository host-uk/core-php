<?php

declare(strict_types=1);

namespace Core\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'title',
        'description',
        'canonical_url',
        'og_data',
        'twitter_data',
        'schema_markup',
        'robots',
        'focus_keyword',
        'seo_score',
        'seo_issues',
        'seo_suggestions',
    ];

    protected $casts = [
        'og_data' => 'array',
        'twitter_data' => 'array',
        'schema_markup' => 'array',
        'seo_issues' => 'array',
        'seo_suggestions' => 'array',
        'seo_score' => 'integer',
    ];

    /**
     * Get the parent seoable model.
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate JSON-LD script tag.
     *
     * Uses JSON_HEX_TAG to prevent XSS via </script> in content.
     */
    public function getJsonLdAttribute(): string
    {
        if (empty($this->schema_markup)) {
            return '';
        }

        return '<script type="application/ld+json">'.
            json_encode($this->schema_markup, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG).
            '</script>';
    }

    /**
     * Generate all meta tags as HTML.
     */
    public function getMetaTagsAttribute(): string
    {
        $tags = [];

        if ($this->title) {
            $tags[] = '<title>'.e($this->title).'</title>';
        }

        if ($this->description) {
            $tags[] = '<meta name="description" content="'.e($this->description).'">';
        }

        if ($this->canonical_url) {
            $tags[] = '<link rel="canonical" href="'.e($this->canonical_url).'">';
        }

        if ($this->robots) {
            $tags[] = '<meta name="robots" content="'.e($this->robots).'">';
        }

        // Open Graph tags
        if (! empty($this->og_data)) {
            foreach ($this->og_data as $property => $content) {
                if ($content) {
                    $tags[] = '<meta property="og:'.$property.'" content="'.e($content).'">';
                }
            }
        }

        // Twitter Card tags
        if (! empty($this->twitter_data)) {
            foreach ($this->twitter_data as $name => $content) {
                if ($content) {
                    $tags[] = '<meta name="twitter:'.$name.'" content="'.e($content).'">';
                }
            }
        }

        return implode("\n    ", $tags);
    }

    /**
     * Get SEO score colour for UI display.
     */
    public function getScoreColorAttribute(): string
    {
        if ($this->seo_score === null) {
            return 'zinc';
        }

        return match (true) {
            $this->seo_score >= 80 => 'green',
            $this->seo_score >= 50 => 'amber',
            default => 'red',
        };
    }

    /**
     * Check if there are issues to address.
     */
    public function hasIssues(): bool
    {
        return ! empty($this->seo_issues);
    }

    /**
     * Get the count of issues.
     */
    public function getIssueCountAttribute(): int
    {
        return count($this->seo_issues ?? []);
    }
}
