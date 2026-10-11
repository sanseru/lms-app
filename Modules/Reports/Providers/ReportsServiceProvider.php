<?php

namespace Modules\Reports\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Modules\Reports\Console\SendMonthlyReportsCommand;

/**
 * Laporan belajar bulanan untuk orang tua: dirangkum dari absensi, materi,
 * nilai, proyek, dan prestasi, ditambah catatan guru per kelas. Bisa dicetak
 * atau disimpan sebagai PDF dari browser.
 */
class ReportsServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Reports';
    }

    public function permissions(): array
    {
        return [
            'reports.view' => ['admin', 'teacher', 'guardian', 'student'],
            'reports.note' => ['admin', 'teacher'],
        ];
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([SendMonthlyReportsCommand::class]);
        }
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Laporan Bulanan', 'reports.index', 'file-text', 'Akademik', 40, roles: ['admin', 'teacher'], active: 'reports.*'),
            new NavItem('Laporan Bulanan', 'reports.index', 'file-text', 'Utama', 20, roles: ['guardian'], active: 'reports.*'),
            new NavItem('Laporan Bulananku', 'reports.index', 'file-text', 'Belajar', 60, roles: ['student'], active: 'reports.*'),
        );
    }
}
