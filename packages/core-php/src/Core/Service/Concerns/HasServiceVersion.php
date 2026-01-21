<?php

declare(strict_types=1);

namespace Core\Service\Concerns;

use Core\Service\ServiceVersion;

/**
 * Default implementation of service versioning.
 *
 * Use this trait in ServiceDefinition implementations to provide
 * backward-compatible default versioning. Override version() to
 * specify a custom version or deprecation status.
 *
 * Example:
 * ```php
 * class MyService implements ServiceDefinition
 * {
 *     use HasServiceVersion;
 *
 *     // Uses default version 1.0.0
 * }
 * ```
 *
 * Or with custom version:
 * ```php
 * class MyService implements ServiceDefinition
 * {
 *     use HasServiceVersion;
 *
 *     public static function version(): ServiceVersion
 *     {
 *         return new ServiceVersion(2, 3, 1);
 *     }
 * }
 * ```
 */
trait HasServiceVersion
{
    /**
     * Get the service contract version.
     *
     * Override this method to specify a custom version or deprecation.
     */
    public static function version(): ServiceVersion
    {
        return ServiceVersion::initial();
    }
}
