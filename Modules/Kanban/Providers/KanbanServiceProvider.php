<?php

namespace Modules\Kanban\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Gate;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Project;
use Modules\Kanban\Policies\BoardPolicy;
use Modules\Kanban\Policies\ProjectPolicy;

/**
 * Modul Kanban proyek: guru membuat proyek untuk kelas beserta langkah bawaan,
 * setiap siswa mengerjakannya di papan kanban sendiri, guru mengecek kartu
 * di kolom "Minta dicek guru". Orang tua melihat papan anaknya.
 */
class KanbanServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Kanban';
    }

    public function permissions(): array
    {
        return ['kanban.manage' => ['admin', 'teacher']];
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Board::class, BoardPolicy::class);
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Proyek', 'kanban.projects.index', 'kanban', 'Belajar', 34, roles: ['admin', 'teacher'], active: 'kanban.*'),
            new NavItem('Proyekku', 'kanban.projects.index', 'kanban', 'Belajar', 34, roles: ['student'], active: 'kanban.*'),
            new NavItem('Proyek Anak', 'kanban.projects.index', 'kanban', 'Belajar', 34, roles: ['guardian'], active: 'kanban.*'),
        );
    }
}
