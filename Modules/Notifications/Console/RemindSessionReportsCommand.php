<?php

namespace Modules\Notifications\Console;

use Illuminate\Console\Command;
use Modules\Notifications\Support\Notify;
use Modules\Sessions\Models\ClassSession;

/** Ingatkan guru yang belum mengisi absensi dan catatan sesi yang sudah lewat. */
class RemindSessionReportsCommand extends Command
{
    protected $signature = 'sessions:remind-reports {--hours=2 : Jeda setelah sesi berakhir sebelum guru diingatkan}';

    protected $description = 'Kirim pengingat ke guru yang belum mengisi laporan sesi';

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
                    "Laporan sesi {$session->classRoom?->name} belum diisi",
                    'Sesi '.$session->starts_at->translatedFormat('l, d M H:i').'. Isi absensi dan catatan agar kuota, honor, dan laporan orang tua ikut tercatat.',
                    route('sessions.show', $session),
                    'clipboard-check',
                    "report-reminder:{$session->id}",
                );
                $sent++;
            });

        $this->info("{$sent} sesi diperiksa untuk pengingat.");

        return self::SUCCESS;
    }
}
