<?php

namespace Modules\Sessions\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Gate;
use Modules\Classes\Models\ClassRoom;
use Modules\Sessions\Actions\GenerateSessions;
use Modules\Sessions\Console\GenerateSessionsCommand;
use Modules\Sessions\Models\ClassSession;
use Modules\Sessions\Policies\ClassSessionPolicy;

class SessionsServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Sessions';
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(ClassSession::class, ClassSessionPolicy::class);

        ClassRoom::resolveRelationUsing('sessions', fn (ClassRoom $class) => $class->hasMany(ClassSession::class));

        // Jadwal kelas dibuat/diubah: susun ulang sesi mendatang.
        ClassRoom::saved(function (ClassRoom $class) {
            if ($class->wasRecentlyCreated || $class->wasChanged(['schedule_days', 'start_time', 'end_time', 'start_date', 'end_date', 'status', 'teacher_id'])) {
                app(GenerateSessions::class)->regenerateUpcoming($class);
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([GenerateSessionsCommand::class]);
        }
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(new NavItem('Sesi & Absensi', 'sessions.index', 'clipboard-check', 'Akademik', 15, roles: ['admin', 'teacher'], active: 'sessions.*'));
    }
}
