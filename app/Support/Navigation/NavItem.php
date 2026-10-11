<?php

namespace App\Support\Navigation;

final class NavItem
{
    /**
     * @param  array<int, string>  $roles  Roles that may see the item (empty = every role).
     * @param  string|null  $permission  Required permission (optional).
     * @param  string|null  $active  Route name pattern for the active state, e.g. "people.students.*".
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $icon,
        public string $group = 'Main',
        public int $order = 100,
        public array $roles = [],
        public ?string $permission = null,
        public ?string $active = null,
    ) {}
}
