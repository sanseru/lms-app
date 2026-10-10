<?php

namespace App\Support\Navigation;

final class NavItem
{
    /**
     * @param  array<int, string>  $roles  Role yang boleh melihat menu (kosong = semua role).
     * @param  string|null  $permission  Permission yang dibutuhkan (opsional).
     * @param  string|null  $active  Pola nama route untuk status aktif, mis. "people.students.*".
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $icon,
        public string $group = 'Utama',
        public int $order = 100,
        public array $roles = [],
        public ?string $permission = null,
        public ?string $active = null,
    ) {}
}
