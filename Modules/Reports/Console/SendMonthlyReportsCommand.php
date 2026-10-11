<?php

namespace Modules\Reports\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Identity\Enums\Role;
use Modules\Notifications\Support\Notify;

/** Beri tahu orang tua bahwa laporan bulan lalu anaknya sudah bisa dibaca. */
class SendMonthlyReportsCommand extends Command
{
    protected $signature = 'reports:monthly {--month= : Bulan laporan (YYYY-MM), bawaan bulan lalu}';

    protected $description = 'Kirim notifikasi laporan bulanan ke orang tua';

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
                    "Laporan belajar {$student->name} bulan {$label}",
                    'Kehadiran, catatan guru, nilai, dan prestasi bulan ini sudah bisa dibaca dan disimpan sebagai PDF.',
                    route('reports.show', [$student, $month]),
                    'file-text',
                    "monthly-report:{$student->id}:{$month}",
                );
                $sent += $student->guardians->count();
            });

        $this->info("{$sent} notifikasi laporan {$label} diproses.");

        return self::SUCCESS;
    }
}
