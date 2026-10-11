<?php

namespace Modules\Gamification\Support;

use App\Models\User;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Modules\Gamification\Contracts\XpRule;

/** Aturan XP dari satu query yang mengembalikan kolom source_id dan at. */
class QueryRule implements XpRule
{
    /** @param  Closure(User): Builder|\Illuminate\Database\Eloquent\Builder  $query */
    public function __construct(
        private string $key,
        private string $label,
        private int $points,
        private Closure $query,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function points(): int
    {
        return $this->points;
    }

    public function events(User $student): Collection
    {
        return collect(($this->query)($student)->get())
            ->map(fn ($row) => ['source_id' => (int) $row->source_id, 'at' => $row->at]);
    }
}
