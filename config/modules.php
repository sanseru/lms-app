<?php

use Modules\Classes\Providers\ClassesServiceProvider;
use Modules\Dashboard\Providers\DashboardServiceProvider;
use Modules\Finance\Providers\FinanceServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\People\Providers\PeopleServiceProvider;

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
        DashboardServiceProvider::class,
        PeopleServiceProvider::class,
        ClassesServiceProvider::class,
        FinanceServiceProvider::class,
    ],
];
