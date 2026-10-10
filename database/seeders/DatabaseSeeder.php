<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
