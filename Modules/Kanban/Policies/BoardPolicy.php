<?php

namespace Modules\Kanban\Policies;

use App\Models\User;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Card;

class BoardPolicy
{
    public function view(User $user, Board $board): bool
    {
        return $board->student_id === $user->id
            || $board->project->isStaff($user)
            || $user->children()->whereKey($board->student_id)->exists();
    }

    /** Adding and moving cards: the board owner while the project is active, or the teacher. */
    public function work(User $user, Board $board): bool
    {
        return ($board->student_id === $user->id && $board->project->status === 'active')
            || $board->project->isStaff($user);
    }

    public function review(User $user, Board $board): bool
    {
        return $board->project->isStaff($user);
    }

    /** Students may only edit or delete their own cards that are not done yet. */
    public function editCard(User $user, Board $board, Card $card): bool
    {
        if ($board->project->isStaff($user)) {
            return true;
        }

        return $this->work($user, $board) && $card->template_task_id === null && $card->column !== 'done';
    }

    /** Students cannot move cards into or out of Done when the project needs teacher review. */
    public function moveCard(User $user, Board $board, Card $card, string $to): bool
    {
        if (! $this->work($user, $board)) {
            return false;
        }

        if ($board->project->isStaff($user) || ! $board->project->requires_review) {
            return true;
        }

        return $to !== 'done' && $card->column !== 'done';
    }
}
