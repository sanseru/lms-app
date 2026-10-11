<?php

namespace Modules\Kanban\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Card;

class BoardController extends Controller
{
    public function show(Request $request, Board $board): Response
    {
        Gate::authorize('view', $board);
        $user = $request->user();
        $board->load(['project.classRoom', 'student', 'cards.reviewer']);
        $project = $board->project;
        $isStaff = $project->isStaff($user);

        $siblings = $isStaff
            ? $project->boards()->with('student:id,name')->get()->sortBy('student.name')->values()
            : collect();
        $at = $siblings->search(fn (Board $b) => $b->id === $board->id);

        return Inertia::render('Kanban::Boards/Show', [
            'project' => $project->toCard(),
            'board' => $board->toSummary() + ['result_note' => $board->result_note],
            'cards' => $board->cards->map->toItem(),
            'columns' => Card::COLUMNS,
            'can' => [
                'work' => $user->can('work', $board),
                'review' => $user->can('review', $board),
                'mine' => $board->student_id === $user->id,
            ],
            'nav' => $isStaff ? [
                'prev' => $at > 0 ? ['id' => $siblings[$at - 1]->id, 'name' => $siblings[$at - 1]->student->name] : null,
                'next' => $at !== false && $at < $siblings->count() - 1 ? ['id' => $siblings[$at + 1]->id, 'name' => $siblings[$at + 1]->student->name] : null,
            ] : null,
        ]);
    }

    /** Tautan hasil proyek (mis. link Scratch atau Replit) dan catatan siswa. */
    public function result(Request $request, Board $board): RedirectResponse
    {
        Gate::authorize('work', $board);
        $data = $request->validate([
            'result_url' => ['nullable', 'url', 'max:255'],
            'result_note' => ['nullable', 'string', 'max:2000'],
        ], [], ['result_url' => 'tautan proyek', 'result_note' => 'catatan']);

        $board->update($data);

        return back()->with('success', 'Hasil proyek tersimpan.');
    }
}
