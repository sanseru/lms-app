<?php

namespace Modules\Reports\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Modules\Reports\Console\SendMonthlyReportsCommand;

/**
 * Monthly learning reports for parents: summarised from attendance, lessons,
 * scores, projects and achievements, plus teacher notes per class. Can be
 * printed or saved as a PDF from the browser.
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
            new NavItem('Monthly reports', 'reports.index', 'file-text', 'Academic', 40, roles: ['admin', 'teacher'], active: 'reports.*'),
            new NavItem('Monthly reports', 'reports.index', 'file-text', 'Main', 20, roles: ['guardian'], active: 'reports.*'),
            new NavItem('My monthly reports', 'reports.index', 'file-text', 'Learning', 60, roles: ['student'], active: 'reports.*'),
        );
    }
}
