<?php

namespace App\Support\Navigation;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Sidebar menu items registered by each module.
 */
class NavigationRegistry
{
    /** @var array<int, NavItem> */
    protected array $items = [];

    /** Order of the menu groups in the sidebar. */
    protected array $groupOrder = ['Main', 'Academic', 'Learning', 'People', 'Finance', 'Other'];

    /** Legacy Indonesian group names, mapped so modules that still use them land in the right group. */
    protected array $groupAliases = [
        'Utama' => 'Main',
        'Akademik' => 'Academic',
        'Belajar' => 'Learning',
        'Pengguna' => 'People',
        'Keuangan' => 'Finance',
        'Lainnya' => 'Other',
    ];

    public function add(NavItem ...$items): static
    {
        array_push($this->items, ...$items);

        return $this;
    }

    /**
     * Menu items the user may see, grouped.
     *
     * @return array<int, array{label: string, items: array<int, array<string, string>>}>
     */
    public function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $visible = collect($this->items)
            ->filter(fn (NavItem $item) => Route::has($item->route))
            ->filter(fn (NavItem $item) => $item->roles === [] || $user->hasAnyRole($item->roles))
            ->filter(fn (NavItem $item) => $item->permission === null || $user->can($item->permission))
            ->sortBy('order');

        return $visible
            ->groupBy(fn (NavItem $item) => $this->groupAliases[$item->group] ?? $item->group)
            ->sortBy(fn ($items, $group) => array_search($group, $this->groupOrder) === false ? 99 : array_search($group, $this->groupOrder))
            ->map(fn ($items, $group) => [
                'label' => $group,
                'items' => $items->map(fn (NavItem $item) => [
                    'label' => $item->label,
                    'href' => route($item->route),
                    'icon' => $item->icon,
                    'active' => $item->active ?? $item->route,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
