<?php

declare(strict_types=1);

namespace Website\Demo\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure the application is installed.
 *
 * Redirects to the install wizard if:
 * - Database tables don't exist
 * - No users have been created
 */
class EnsureInstalled
{
    /**
     * Routes that should be accessible even when not installed.
     */
    protected array $except = [
        'install',
        'install/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for install routes
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Check if app needs installation
        if ($this->needsInstallation()) {
            return redirect()->route('install');
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function needsInstallation(): bool
    {
        try {
            // Check if users table exists and has at least one user
            if (! Schema::hasTable('users')) {
                return true;
            }

            // Check if any users exist
            return \DB::table('users')->count() === 0;
        } catch (\Exception $e) {
            // Database connection failed - needs installation
            return true;
        }
    }
}
