<?php

namespace Modules\Kanban\Policies;

use App\Models\User;
use Modules\Kanban\Models\Project;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return Project::query()->visibleTo($user)->whereKey($project->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('kanban.manage');
    }

    public function manage(User $user, Project $project): bool
    {
        return $project->isStaff($user);
    }
}
