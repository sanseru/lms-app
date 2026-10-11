<?php

namespace Modules\Lab\Policies;

use App\Models\User;
use Modules\Lab\Models\LabComment;

class LabCommentPolicy
{
    /** The writer, the project owner, or a moderator can remove a comment. */
    public function delete(User $user, LabComment $comment): bool
    {
        return $comment->user_id === $user->id
            || $comment->project->user_id === $user->id
            || $user->can('community.moderate');
    }
}
