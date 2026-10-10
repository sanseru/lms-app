<?php

namespace App\Support\Navigation;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Kumpulan menu sidebar yang didaftarkan oleh setiap modul.
 */
class NavigationRegistry
{
    /** @var array<int, NavItem> */
    protected array $items = [];

    /** Urutan grup menu di sidebar. */
    protected array $groupOrder = ['Utama', 'Akademik', 'Pengguna', 'Keuangan', 'Lainnya'];

    public function add(NavItem ...$items): static
    {
        array_push($this->items, ...$items);

        return $this;
    }

    /**
     * Menu yang boleh dilihat user, dikelompokkan per grup.
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
            ->groupBy('group')
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
