<?php

namespace Modules\Lab\Policies;

use App\Models\User;
use Modules\Lab\Models\LabProject;

class LabProjectPolicy
{
    /** Open in the Laboratory editor: only the owner. */
    public function update(User $user, LabProject $project): bool
    {
        return $project->user_id === $user->id;
    }

    public function delete(User $user, LabProject $project): bool
    {
        return $project->user_id === $user->id;
    }

    /** Seen in the Community: shared projects, or your own; moderators also see hidden ones. */
    public function view(User $user, LabProject $project): bool
    {
        return $project->user_id === $user->id
            || $project->isShared()
            || ($project->shared_at !== null && $user->can('community.moderate'));
    }

    /**
     * Likes and comments on a live project: everyone who is logged in (students, teachers,
     * parents, admins), as in the client prototype. Teachers can still remove comments.
     */
    public function interact(User $user, LabProject $project): bool
    {
        return $project->isShared();
    }

    public function moderate(User $user, LabProject $project): bool
    {
        return $project->shared_at !== null && $user->can('community.moderate');
    }
}
