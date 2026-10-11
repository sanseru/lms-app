<?php

namespace Modules\Dashboard\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;

class DashboardServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Dashboard';
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(new NavItem('Home', 'dashboard', 'layout-dashboard', 'Main', 0));
    }
}
