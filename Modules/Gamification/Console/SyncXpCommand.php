<?php

namespace Modules\Gamification\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Gamification\Actions\SyncXp;

class SyncXpCommand extends Command
{
    protected $signature = 'xp:sync {--user= : ID siswa tertentu}';

    protected $description = 'Hitung ulang XP semua siswa dari aktivitas belajar';

    public function handle(SyncXp $sync): int
    {
        $students = User::role('student')->when($this->option('user'), fn ($q, $id) => $q->whereKey($id))->get();
        $students->each(fn (User $u) => $sync->handle($u));
        $this->info("XP {$students->count()} siswa diperbarui.");

        return self::SUCCESS;
    }
}
