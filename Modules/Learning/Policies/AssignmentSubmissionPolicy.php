<?php

namespace Modules\Learning\Policies;

use App\Models\User;
use Modules\Learning\Models\AssignmentSubmission;

class AssignmentSubmissionPolicy
{
    public function view(User $user, AssignmentSubmission $submission): bool
    {
        return $submission->student_id === $user->id
            || $user->children()->whereKey($submission->student_id)->exists()
            || $this->grade($user, $submission);
    }

    public function grade(User $user, AssignmentSubmission $submission): bool
    {
        return AssignmentSubmission::query()->gradableBy($user)->whereKey($submission->id)->exists();
    }
}
