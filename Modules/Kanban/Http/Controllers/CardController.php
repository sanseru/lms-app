<?php

namespace Modules\Kanban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Kanban\Actions\MoveCard;
use Modules\Kanban\Http\Requests\CardRequest;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Card;

class CardController extends Controller
{
    public function store(CardRequest $request, Board $board): RedirectResponse
    {
        $board->cards()->create($request->validated() + [
            'created_by' => $request->user()->id,
            'column' => 'todo',
            'position' => (int) $board->cards()->where('column', 'todo')->max('position') + 1,
        ]);
        $board->refreshCompletion();

        return back();
    }

    public function update(CardRequest $request, Card $card): RedirectResponse
    {
        $card->update($request->validated());

        return back();
    }

    public function destroy(Request $request, Card $card): RedirectResponse
    {
        $board = $card->board;
        Gate::authorize('editCard', [$board, $card]);
        $card->delete();
        $board->refreshCompletion();

        return back();
    }

    public function move(Request $request, Card $card, MoveCard $move): RedirectResponse
    {
        $data = $request->validate([
            'column' => ['required', Rule::in(array_keys(Card::COLUMNS))],
            'index' => ['required', 'integer', 'min:0'],
        ]);
        Gate::authorize('moveCard', [$card->board, $card, $data['column']]);

        $move->handle($card, $data['column'], $data['index'], $request->user());

        return back();
    }

    /** The teacher approves a card (moves it to Done) or sends it back with a note. */
    public function review(Request $request, Card $card, MoveCard $move): RedirectResponse
    {
        Gate::authorize('review', $card->board);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'return'])],
            'feedback' => ['nullable', 'required_if:decision,return', 'string', 'max:2000'],
        ], ['feedback.required_if' => 'Write a note so the student knows what to fix.']);

        $approve = $data['decision'] === 'approve';
        $card->update(['feedback' => $data['feedback'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $move->handle($card, $approve ? 'done' : 'doing', $approve ? PHP_INT_MAX : 0, $request->user());

        return back()->with('success', $approve ? 'Card approved.' : 'Card sent back with a note.');
    }
}
