<?php

namespace Modules\Forum\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Forum\Actions\PostAnswer;
use Modules\Forum\Actions\ToggleVote;
use Modules\Forum\Http\Requests\AnswerRequest;
use Modules\Forum\Models\Answer;
use Modules\Forum\Models\Question;

class AnswerController extends Controller
{
    public function store(AnswerRequest $request, Question $question, PostAnswer $post): RedirectResponse
    {
        $post->handle($question, $request->user(), $request->validated());

        return back()->with('success', 'Jawaban terkirim. Terima kasih sudah membantu!');
    }

    public function update(AnswerRequest $request, Answer $answer): RedirectResponse
    {
        $answer->update($request->validated());

        return back()->with('success', 'Jawaban diperbarui.');
    }

    public function destroy(Answer $answer): RedirectResponse
    {
        Gate::authorize('delete', $answer);
        $question = $answer->question;

        $answer->votes()->delete();
        $answer->delete();

        $question->forceFill([
            'answers_count' => $question->answers()->count(),
            'accepted_answer_id' => $question->accepted_answer_id === $answer->id ? null : $question->accepted_answer_id,
            'resolved_at' => $question->accepted_answer_id === $answer->id ? null : $question->resolved_at,
            'staff_answered_at' => $question->answers()->where('by_staff', true)->min('created_at'),
        ])->saveQuietly();

        return back()->with('success', 'Jawaban dihapus.');
    }

    /** Pilih (atau batalkan) jawaban terbaik; memilih juga menandai pertanyaan terjawab. */
    public function accept(Answer $answer): RedirectResponse
    {
        $question = $answer->question;
        Gate::authorize('resolve', $question);

        $undo = $question->accepted_answer_id === $answer->id;
        $question->update([
            'accepted_answer_id' => $undo ? null : $answer->id,
            'resolved_at' => $undo ? null : now(),
        ]);

        return back()->with('success', $undo ? 'Pilihan jawaban terbaik dibatalkan.' : 'Jawaban terbaik dipilih. Pertanyaan terjawab!');
    }

    public function vote(Request $request, Answer $answer, ToggleVote $toggle): RedirectResponse
    {
        Gate::authorize('vote', $answer);
        $toggle->handle($answer, $request->user());

        return back();
    }

    public function voteQuestion(Request $request, Question $question, ToggleVote $toggle): RedirectResponse
    {
        Gate::authorize('vote', $question);
        $toggle->handle($question, $request->user());

        return back();
    }
}
