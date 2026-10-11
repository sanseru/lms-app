<?php

namespace Modules\Forum\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Add or remove an upvote, then recount votes_count. */
class ToggleVote
{
    public function handle(Model $votable, User $user): bool
    {
        $existing = $votable->votes()->where('user_id', $user->id)->first();

        $existing ? $existing->delete() : $votable->votes()->create(['user_id' => $user->id]);

        $votable->forceFill(['votes_count' => $votable->votes()->count()])->saveQuietly();

        return ! $existing;
    }
}
