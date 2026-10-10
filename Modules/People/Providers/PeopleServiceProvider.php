<?php

namespace Modules\People\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;

class PeopleServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'People';
    }

    public function permissions(): array
    {
        return [
            'people.students.manage' => ['admin'],
            'people.teachers.manage' => ['admin'],
            'people.guardians.manage' => ['admin'],
        ];
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Siswa', 'people.students.index', 'graduation-cap', 'Pengguna', 10, permission: 'people.students.manage', active: 'people.students.*'),
            new NavItem('Guru', 'people.teachers.index', 'presentation', 'Pengguna', 20, permission: 'people.teachers.manage', active: 'people.teachers.*'),
            new NavItem('Orang Tua', 'people.guardians.index', 'users', 'Pengguna', 30, permission: 'people.guardians.manage', active: 'people.guardians.*'),
        );
    }
}
