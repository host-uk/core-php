<?php

declare(strict_types=1);

namespace Core\Service\Contracts;

use Core\Front\Admin\Contracts\AdminMenuProvider;

/**
 * Contract for service definitions.
 *
 * Services are the product layer - they define how modules are presented
 * to users as SaaS products. Each service has a definition used to populate
 * the platform_services table and admin menu registration.
 *
 * Extends AdminMenuProvider to integrate with the admin menu system.
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
}
