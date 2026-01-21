<?php

declare(strict_types=1);

namespace Core\Front\Admin;

/**
 * Shared context for tab components.
 *
 * Allows parent <admin:tabs> to communicate selected state
 * to child <admin:tab.panel> components without explicit prop passing.
 */
class TabContext
{
    public static ?string $selected = null;
}
