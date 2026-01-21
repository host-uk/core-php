<?php

declare(strict_types=1);

namespace Core\Headers;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add security headers to all responses.
 *
 * Headers added:
 * - Strict-Transport-Security (HSTS) - enforce HTTPS
 * - Content-Security-Policy - restrict resource loading
 * - X-Content-Type-Options - prevent MIME sniffing
 * - X-Frame-Options - prevent clickjacking
 * - X-XSS-Protection - enable browser XSS filtering
 * - Referrer-Policy - control referrer information
 * - Permissions-Policy - control browser features
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Strict Transport Security - enforce HTTPS for 1 year
        // Only add in production to avoid issues with local development
        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Get domain from config
        $baseDomain = config('core.domain.base', 'core.test');
        $cdnSubdomain = config('core.cdn.subdomain', 'cdn');

        // Content Security Policy - restrict resource loading
        $connectSrc = "'self' https://*.{$baseDomain} wss://*.{$baseDomain} wss://{$baseDomain}:8080 https://raw.githubusercontent.com";

        // Allow localhost WebSocket in development
        if (! app()->environment('production')) {
            $connectSrc .= ' wss://localhost:8080 ws://localhost:8080 wss://127.0.0.1:8080 ws://127.0.0.1:8080';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://{$cdnSubdomain}.{$baseDomain} https://cdn.jsdelivr.net https://unpkg.com https://www.googletagmanager.com https://connect.facebook.net",
            "style-src 'self' 'unsafe-inline' https://{$cdnSubdomain}.{$baseDomain} https://fonts.bunny.net https://fonts.googleapis.com https://unpkg.com",
            "img-src 'self' data: https: blob:",
            "font-src 'self' https://{$cdnSubdomain}.{$baseDomain} https://fonts.bunny.net https://fonts.gstatic.com data:",
            "connect-src {$connectSrc}",
            "frame-src 'self' https://www.youtube.com https://player.vimeo.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Enable XSS filtering in browsers that support it
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information sent with requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
        );

        return $response;
    }
}
