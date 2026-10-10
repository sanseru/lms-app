<?php

namespace Modules\Identity\Database\Seeders;

use App\Support\Modules\ModuleManager;
use Illuminate\Database\Seeder;
use Modules\Identity\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Membuat role dasar dan semua permission yang dideklarasikan modul aktif.
 * Aman dijalankan berulang kali.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(ModuleManager $modules): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        foreach ($modules->permissions() as $permission => $roles) {
            Permission::findOrCreate($permission, 'web');

            foreach ($roles as $role) {
                Role::findByName($role, 'web')->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
