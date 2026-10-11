<?php

namespace Modules\Notifications\Console;

use Illuminate\Console\Command;
use Modules\Notifications\Support\Notify;
use Modules\Sessions\Models\ClassSession;

/** Remind teachers who have not filled in attendance and notes for past sessions. */
class RemindSessionReportsCommand extends Command
{
    protected $signature = 'sessions:remind-reports {--hours=2 : Delay after a session ends before the teacher is reminded}';

    protected $description = 'Send reminders to teachers who have not filled in session reports';

    public function handle(): int
    {
        $sent = 0;

        ClassSession::query()
            ->needsReport()
            ->where('ends_at', '<=', now()->subHours((int) $this->option('hours')))
            ->where('ends_at', '>=', now()->subDays(14))
            ->whereNotNull('teacher_id')
            ->with(['teacher', 'classRoom:id,name'])
            ->each(function (ClassSession $session) use (&$sent) {
                Notify::send(
                    $session->teacher,
                    "Session report for {$session->classRoom?->name} is not filled in yet",
                    'Session on '.$session->starts_at->translatedFormat('l, d M H:i').'. Fill in attendance and notes so quotas, tutor fees and parent reports are recorded.',
                    route('sessions.show', $session),
                    'clipboard-check',
                    "report-reminder:{$session->id}",
                );
                $sent++;
            });

        $this->info("{$sent} sessions checked for reminders.");

        return self::SUCCESS;
    }
}
