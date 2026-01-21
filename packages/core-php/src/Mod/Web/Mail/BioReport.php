<?php

declare(strict_types=1);

namespace Core\Mod\Web\Mail;

use Core\Mod\Web\Models\Page;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email report for biolink analytics.
 *
 * Sends a formatted summary of clicks, countries, devices, and referrers
 * for a specified date range.
 */
class BioReport extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Page $biolink,
        public array $analytics,
        public Carbon $startDate,
        public Carbon $endDate,
        public string $frequency = 'weekly',
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $periodLabel = match ($this->frequency) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => '',
        };

        $dateRange = $this->startDate->isSameDay($this->endDate)
            ? $this->startDate->format('j M Y')
            : $this->startDate->format('j M').' - '.$this->endDate->format('j M Y');

        return new Envelope(
            subject: "[BioHost] {$periodLabel} Report for /{$this->biolink->url} ({$dateRange})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'webpage::emails.biolink-report',
            with: [
                'biolink' => $this->biolink,
                'analytics' => $this->analytics,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'frequency' => $this->frequency,
                'dateRange' => $this->getDateRangeLabel(),
                'summary' => $this->analytics['summary'] ?? [],
                'countries' => $this->analytics['countries'] ?? [],
                'devices' => $this->analytics['devices'] ?? [],
                'referrers' => $this->analytics['referrers'] ?? [],
                'viewUrl' => $this->getViewUrl(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get a human-readable date range label.
     */
    protected function getDateRangeLabel(): string
    {
        if ($this->startDate->isSameDay($this->endDate)) {
            return $this->startDate->format('j F Y');
        }

        if ($this->startDate->isSameMonth($this->endDate)) {
            return $this->startDate->format('j').'-'.$this->endDate->format('j F Y');
        }

        return $this->startDate->format('j M').' - '.$this->endDate->format('j M Y');
    }

    /**
     * Get the URL to view full analytics.
     */
    protected function getViewUrl(): string
    {
        return route('hub.bio.analytics', ['biolink' => $this->biolink->id]);
    }
}
