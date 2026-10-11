<?php

namespace Modules\Gamification\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * A rule for awarding XP. Other modules can register their own rules via
 * app(XpRules::class)->add(...). XP is recalculated from the source data, so a
 * rule only has to answer "which events has this student achieved".
 */
interface XpRule
{
    public function key(): string;

    public function label(): string;

    public function points(): int;

    /** @return Collection<int, array{source_id: int, at: \DateTimeInterface|string}> */
    public function events(User $student): Collection;
}
