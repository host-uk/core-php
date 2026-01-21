<?php

declare(strict_types=1);

namespace Core\Mod\Web\Middleware;

use Core\Mod\Web\Models\Page;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce password protection on bio.
 *
 * Password protection settings are stored in bio.settings:
 * {
 *   "password_protected": true,
 *   "password": "$2y$10$...", // bcrypt hash
 *   "password_hint": "Optional hint for the password"
 * }
 *
 * Once the user enters the correct password, access is stored in the
 * session and they won't be prompted again for that bio.
 *
 * Session key: biolink_access_{biolink_id}
 */
class BioPasswordProtection
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the biolink from the request (set by controller or earlier middleware)
        $biolink = $request->attributes->get('biolink');

        if (! $biolink instanceof Page) {
            // No biolink resolved yet, let the request continue
            return $next($request);
        }

        // Check if password protection is enabled
        if (! $this->isPasswordProtected($biolink)) {
            return $next($request);
        }

        // Check if user has already verified the password
        if ($this->hasAccess($biolink, $request)) {
            return $next($request);
        }

        // Show the password form
        return $this->showPasswordForm($biolink);
    }

    /**
     * Check if the biolink has password protection enabled.
     */
    protected function isPasswordProtected(Page $biolink): bool
    {
        return (bool) $biolink->getSetting('password_protected', false);
    }

    /**
     * Check if the user has already verified the password for this bio.
     */
    protected function hasAccess(Page $biolink, Request $request): bool
    {
        $sessionKey = $this->getSessionKey($biolink);

        return $request->session()->has($sessionKey);
    }

    /**
     * Grant access to a biolink (store in session).
     */
    public function grantAccess(Page $biolink, Request $request): void
    {
        $sessionKey = $this->getSessionKey($biolink);
        $request->session()->put($sessionKey, true);
    }

    /**
     * Revoke access to a biolink (remove from session).
     */
    public function revokeAccess(Page $biolink, Request $request): void
    {
        $sessionKey = $this->getSessionKey($biolink);
        $request->session()->forget($sessionKey);
    }

    /**
     * Get the session key for a bio.
     */
    protected function getSessionKey(Page $biolink): string
    {
        return 'biolink_access_'.$biolink->id;
    }

    /**
     * Show the password form.
     */
    protected function showPasswordForm(Page $biolink, ?string $error = null): Response
    {
        $hint = $biolink->getSetting('password_hint');

        return response()->view('lthn::bio.password', [
            'biolink' => $biolink,
            'error' => $error,
            'hint' => $hint,
        ], 200, [
            // Prevent caching of password-protected pages
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
