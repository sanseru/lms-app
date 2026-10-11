<?php

namespace Modules\Classes\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Gate;
use Modules\Classes\Models\ClassRoom;
use Modules\Classes\Policies\ClassRoomPolicy;

class ClassesServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Classes';
    }

    public function permissions(): array
    {
        return [
            'classes.manage' => ['admin'],
        ];
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(ClassRoom::class, ClassRoomPolicy::class);
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Classes', 'classes.index', 'school', 'Academic', 10, active: 'classes.*'),
            new NavItem('Schedule', 'schedule.index', 'calendar-days', 'Academic', 20),
        );
    }
}
