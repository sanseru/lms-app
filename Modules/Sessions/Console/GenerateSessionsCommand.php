<?php

namespace Modules\Sessions\Console;

use Illuminate\Console\Command;
use Modules\Classes\Models\ClassRoom;
use Modules\Sessions\Actions\GenerateSessions;

class GenerateSessionsCommand extends Command
{
    protected $signature = 'sessions:generate {--weeks= : Jumlah minggu ke depan}';

    protected $description = 'Buat sesi kelas dari jadwal mingguan untuk beberapa minggu ke depan';

    public function handle(GenerateSessions $generator): int
    {
        $weeks = (int) ($this->option('weeks') ?: config('sessions.generate_weeks_ahead'));
        $total = 0;

        ClassRoom::whereIn('status', ['active', 'upcoming'])->each(function (ClassRoom $class) use ($generator, $weeks, &$total) {
            $total += $generator->forClass($class, now(), now()->addWeeks($weeks));
        });

        $this->info("{$total} sesi baru dibuat.");

        return self::SUCCESS;
    }
}
