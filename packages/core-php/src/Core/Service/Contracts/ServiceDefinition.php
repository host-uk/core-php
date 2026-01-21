<?php

declare(strict_types=1);

namespace Core\Service\Contracts;

use Core\Front\Admin\Contracts\AdminMenuProvider;
use Core\Service\ServiceVersion;

/**
 * Contract for service definitions.
 *
 * Services are the product layer - they define how modules are presented
 * to users as SaaS products. Each service has a definition used to populate
 * the platform_services table and admin menu registration.
 *
 * Extends AdminMenuProvider to integrate with the admin menu system.
 *
 * ## Versioning
 *
 * Services should implement the version() method to declare their contract
 * version. This enables:
 * - Tracking breaking changes in service contracts
 * - Deprecation warnings before removing features
 * - Sunset date enforcement for deprecated versions
 *
 * Example:
 * ```php
 * public static function version(): ServiceVersion
 * {
 *     return new ServiceVersion(2, 1, 0);
 * }
 * ```
 *
 * For deprecated services:
 * ```php
 * public static function version(): ServiceVersion
 * {
 *     return (new ServiceVersion(1, 0, 0))
 *         ->deprecate(
 *             'Use ServiceV2 instead',
 *             new \DateTimeImmutable('2025-06-01')
 *         );
 * }
 * ```
 */
interface ServiceDefinition extends AdminMenuProvider
{
    /**
     * Get the service definition for seeding platform_services.
     *
     * @return array{
     *     code: string,
     *     module: string,
     *     name: string,
     *     tagline?: string,
     *     description?: string,
     *     icon?: string,
     *     color?: string,
     *     entitlement_code?: string,
     *     sort_order?: int,
     * }
     */
    public static function definition(): array;

    /**
     * Get the service contract version.
     *
     * Implementations should return a ServiceVersion indicating the
     * current version of this service's contract. This is used for:
     * - Compatibility checking between service consumers and providers
     * - Deprecation tracking and sunset enforcement
     * - Migration planning when breaking changes are introduced
     *
     * Default implementation returns version 1.0.0 for backward
     * compatibility with existing services.
     */
    public static function version(): ServiceVersion;
}
