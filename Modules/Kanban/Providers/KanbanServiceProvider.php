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
 * Project Kanban module: teachers create projects for a class with default steps,
 * each student works on their own kanban board, and teachers review cards
 * in the "Ready for review" column. Parents can see their child's board.
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
            new NavItem('Projects', 'kanban.projects.index', 'kanban', 'Learning', 34, roles: ['admin', 'teacher'], active: 'kanban.*'),
            new NavItem('My projects', 'kanban.projects.index', 'kanban', 'Learning', 34, roles: ['student'], active: 'kanban.*'),
            new NavItem("Child's projects", 'kanban.projects.index', 'kanban', 'Learning', 34, roles: ['guardian'], active: 'kanban.*'),
        );
    }
}
