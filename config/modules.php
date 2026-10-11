<?php

use Modules\Audit\Providers\AuditServiceProvider;
use Modules\Certificates\Providers\CertificatesServiceProvider;
use Modules\Classes\Providers\ClassesServiceProvider;
use Modules\Coding\Providers\CodingServiceProvider;
use Modules\Dashboard\Providers\DashboardServiceProvider;
use Modules\Finance\Providers\FinanceServiceProvider;
use Modules\Forum\Providers\ForumServiceProvider;
use Modules\Gamification\Providers\GamificationServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Kanban\Providers\KanbanServiceProvider;
use Modules\Lab\Providers\LabServiceProvider;
use Modules\Landing\Providers\LandingServiceProvider;
use Modules\Learning\Providers\LearningServiceProvider;
use Modules\Notifications\Providers\NotificationsServiceProvider;
use Modules\People\Providers\PeopleServiceProvider;
use Modules\Sessions\Providers\SessionsServiceProvider;

/*
|--------------------------------------------------------------------------
| Enabled modules
|--------------------------------------------------------------------------
|
| Every module lives in Modules/<Name> and has its own service provider
| that registers routes, migrations, permissions and navigation items.
| To add a module, create its folder and register its provider here.
| Remove it from the list to disable the module.
|
*/

return [
    'enabled' => [
        IdentityServiceProvider::class,
        LandingServiceProvider::class,
        DashboardServiceProvider::class,
        PeopleServiceProvider::class,
        ClassesServiceProvider::class,
        SessionsServiceProvider::class,
        LearningServiceProvider::class,
        CodingServiceProvider::class,
        ForumServiceProvider::class,
        KanbanServiceProvider::class,
        LabServiceProvider::class,
        GamificationServiceProvider::class,
        CertificatesServiceProvider::class,
        FinanceServiceProvider::class,
        NotificationsServiceProvider::class,
        AuditServiceProvider::class,
    ],
];
