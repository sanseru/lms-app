<?php

namespace Modules\Gamification\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aturan pemberian XP. Modul lain bisa mendaftarkan aturan sendiri lewat
 * app(XpRules::class)->add(...). XP dihitung ulang dari data sumber, jadi
 * aturan cukup menjawab "kejadian apa saja yang sudah dicapai siswa ini".
 */
interface XpRule
{
    public function key(): string;

    public function label(): string;

    public function points(): int;

    /** @return Collection<int, array{source_id: int, at: \DateTimeInterface|string}> */
    public function events(User $student): Collection;
}
