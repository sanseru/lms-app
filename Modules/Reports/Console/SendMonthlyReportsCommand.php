<?php

namespace Modules\Reports\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Identity\Enums\Role;
use Modules\Notifications\Support\Notify;

/** Let parents know their child's report for last month is ready to read. */
class SendMonthlyReportsCommand extends Command
{
    protected $signature = 'reports:monthly {--month= : Report month (YYYY-MM), defaults to last month}';

    protected $description = 'Send monthly report notifications to parents';

    public function handle(): int
    {
        $month = $this->option('month') ?: now()->subMonthNoOverflow()->format('Y-m');
        $label = now()->createFromFormat('!Y-m', $month)->translatedFormat('F Y');
        $sent = 0;

        User::role(Role::Student->value)->where('status', 'active')
            ->whereHas('enrolledClasses')
            ->with('guardians')
            ->each(function (User $student) use ($month, $label, &$sent) {
                Notify::send(
                    $student->guardians,
                    "{$student->name}'s learning report for {$label}",
                    'This month\'s attendance, teacher notes, scores and achievements are ready to read and save as a PDF.',
                    route('reports.show', [$student, $month]),
                    'file-text',
                    "monthly-report:{$student->id}:{$month}",
                );
                $sent += $student->guardians->count();
            });

        $this->info("{$sent} report notifications for {$label} processed.");

        return self::SUCCESS;
    }
}
