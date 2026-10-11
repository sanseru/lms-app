<?php

namespace Modules\Gamification\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Gamification\Actions\SyncXp;

class SyncXpCommand extends Command
{
    protected $signature = 'xp:sync {--user= : A specific student ID}';

    protected $description = 'Recalculate XP for all students from learning activity';

    public function handle(SyncXp $sync): int
    {
        $students = User::role('student')->when($this->option('user'), fn ($q, $id) => $q->whereKey($id))->get();
        $students->each(fn (User $u) => $sync->handle($u));
        $this->info("XP updated for {$students->count()} students.");

        return self::SUCCESS;
    }
}
