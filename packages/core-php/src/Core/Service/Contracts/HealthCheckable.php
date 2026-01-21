<?php

declare(strict_types=1);

namespace Core\Service\Contracts;

use Core\Service\HealthCheckResult;

/**
 * Contract for services that provide health checks.
 *
 * Services implementing this interface can report their operational
 * status for monitoring, load balancing, and alerting purposes.
 *
 * Health checks should be:
 * - Fast (< 5 seconds timeout recommended)
 * - Non-destructive (read-only operations)
 * - Representative of actual service health
 *
 * Example implementation:
 * ```php
 * public function healthCheck(): HealthCheckResult
 * {
 *     try {
 *         $start = microtime(true);
 *         $this->database->select('SELECT 1');
 *         $responseTime = (microtime(true) - $start) * 1000;
 *
 *         if ($responseTime > 1000) {
 *             return HealthCheckResult::degraded(
 *                 'Database responding slowly',
 *                 ['response_time_ms' => $responseTime]
 *             );
 *         }
 *
 *         return HealthCheckResult::healthy(
 *             'All systems operational',
 *             responseTimeMs: $responseTime
 *         );
 *     } catch (\Exception $e) {
 *         return HealthCheckResult::fromException($e);
 *     }
 * }
 * ```
 */
interface HealthCheckable
{
    /**
     * Perform a health check and return the result.
     *
     * Implementations should catch all exceptions and return
     * an appropriate HealthCheckResult rather than throwing.
     */
    public function healthCheck(): HealthCheckResult;
}
