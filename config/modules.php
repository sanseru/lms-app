<?php

use Modules\Classes\Providers\ClassesServiceProvider;
use Modules\Coding\Providers\CodingServiceProvider;
use Modules\Dashboard\Providers\DashboardServiceProvider;
use Modules\Finance\Providers\FinanceServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Landing\Providers\LandingServiceProvider;
use Modules\Learning\Providers\LearningServiceProvider;
use Modules\People\Providers\PeopleServiceProvider;
use Modules\Sessions\Providers\SessionsServiceProvider;

/*
|--------------------------------------------------------------------------
| Modul aktif
|--------------------------------------------------------------------------
|
| Setiap modul berada di folder Modules/<Nama> dan memiliki service
| provider sendiri yang mendaftarkan route, migration, permission, dan
| menu navigasi. Untuk menambah modul baru, buat foldernya lalu daftarkan
| provider-nya di sini. Hapus dari daftar untuk menonaktifkan modul.
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
        FinanceServiceProvider::class,
    ],
];
