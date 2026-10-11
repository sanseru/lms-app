<?php

namespace Modules\Kanban\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Kanban\Models\Card;

/** Move a card to a given column and position, then tidy up the positions. */
class MoveCard
{
    public function handle(Card $card, string $column, int $index, User $by): Card
    {
        return DB::transaction(function () use ($card, $column, $index, $by) {
            $from = $card->column;
            $board = $card->loadMissing('board.project.classRoom')->board;

            $siblings = $board->cards()->where('column', $column)->whereKeyNot($card->id)->orderBy('position')->pluck('id')->all();
            array_splice($siblings, max(0, min($index, count($siblings))), 0, [$card->id]);

            $card->column = $column;
            if ($column === 'done' && $from !== 'done') {
                $card->done_at = now();
                if ($board->project->isStaff($by)) {
                    $card->reviewed_by = $by->id;
                    $card->reviewed_at = now();
                }
            } elseif ($column !== 'done') {
                $card->done_at = null;
            }
            $card->save();

            foreach ($siblings as $i => $id) {
                Card::whereKey($id)->update(['position' => $i + 1]);
            }

            if ($from !== $column) {
                $board->cards()->where('column', $from)->orderBy('position')->pluck('id')
                    ->each(fn ($id, $i) => Card::whereKey($id)->update(['position' => $i + 1]));
            }

            $board->touch();
            $board->refreshCompletion();

            return $card->fresh();
        });
    }
}
