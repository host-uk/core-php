<?php

namespace Core\Mod\Web\Controllers\Web;

use Core\Front\Controller;
use Core\Mod\Web\Jobs\SendSubmissionNotification;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Handle form submissions from public biolink pages.
 *
 * Processes email_collector, phone_collector, and contact_collector blocks.
 * Rate limited per IP to prevent spam.
 */
class SubmissionController extends Controller
{
    /**
     * Store a form submission.
     *
     * POST /api/biolink/submit
     */
    public function store(Request $request): JsonResponse
    {
        // Honeypot spam check - do this first, before validation
        // This silently rejects bots without revealing the honeypot
        if ($request->filled('website')) {
            return response()->json(['ok' => true]);
        }

        // Basic validation
        $validated = $request->validate([
            'block_id' => 'required|integer',
            'type' => 'required|in:email,phone,contact',
            'name' => 'nullable|string|max:128',
            'email' => 'nullable|email|max:256',
            'phone' => 'nullable|string|max:32',
            'message' => 'nullable|string|max:2000',
        ]);

        // Rate limit per IP (5 submissions per minute per IP)
        $ip = $request->ip();
        $rateLimitKey = 'biolink_submission:'.md5($ip);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json([
                'ok' => false,
                'error' => 'Too many submissions. Please wait a moment.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        // Find the block
        $block = Block::with('biolink')->find($validated['block_id']);

        if (! $block || ! $block->biolink || ! $block->is_enabled) {
            return response()->json([
                'ok' => false,
                'error' => 'Form not found.',
            ], 404);
        }

        // Validate type-specific required fields
        $error = $this->validateSubmissionData($validated);
        if ($error) {
            return response()->json(['ok' => false, 'error' => $error], 422);
        }

        // Build data payload (only include fields that have values)
        $data = array_filter([
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        // Get country code from CDN headers
        $countryCode = $request->header('CF-IPCountry')
            ?? $request->header('X-Country-Code');

        if ($countryCode === 'XX') {
            $countryCode = null;
        }

        // Store submission
        $submission = Submission::createFromForm(
            $block,
            $validated['type'],
            $data,
            $ip,
            $countryCode
        );

        // Check if notifications are configured
        $webhookUrl = $block->getSetting('webhook_url');
        $notifyEmail = $block->getSetting('notify_email');

        if ($webhookUrl || $notifyEmail) {
            SendSubmissionNotification::dispatch($submission);
        }

        return response()->json([
            'ok' => true,
            'message' => $block->getSetting('success_message')
                ?? $this->getDefaultSuccessMessage($validated['type']),
        ]);
    }

    /**
     * Validate type-specific required fields.
     */
    protected function validateSubmissionData(array $data): ?string
    {
        return match ($data['type']) {
            'email' => empty($data['email']) ? 'Email is required.' : null,
            'phone' => empty($data['phone']) ? 'Phone number is required.' : null,
            'contact' => match (true) {
                empty($data['email']) => 'Email is required.',
                empty($data['message']) => 'Message is required.',
                default => null,
            },
            default => 'Invalid submission type.',
        };
    }

    /**
     * Get default success message by type.
     */
    protected function getDefaultSuccessMessage(string $type): string
    {
        return match ($type) {
            'email' => 'Thank you for subscribing.',
            'phone' => 'Thank you for subscribing.',
            'contact' => 'Thank you for your message. We will be in touch soon.',
            default => 'Submission received.',
        };
    }
}
