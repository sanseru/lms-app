<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Learning\Models\AssignmentSubmission;

/** Antrean tugas yang perlu dinilai guru. */
class GradingController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = in_array($request->query('tab'), ['submitted', 'revision', 'graded'], true) ? $request->query('tab') : 'submitted';
        $base = AssignmentSubmission::query()->gradableBy($request->user());

        $submissions = (clone $base)
            ->where('status', $tab)
            ->with(['student', 'grader', 'lesson.course'])
            ->orderBy($tab === 'submitted' ? 'submitted_at' : 'updated_at', $tab === 'submitted' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AssignmentSubmission $s) => $s->toItem() + [
                'lesson' => ['id' => $s->lesson->id, 'title' => $s->lesson->title, 'max_score' => $s->lesson->maxScore()],
                'course' => ['id' => $s->lesson->course->id, 'title' => $s->lesson->course->title, 'color' => $s->lesson->course->color],
            ]);

        return Inertia::render('Learning::Grading/Index', [
            'submissions' => $submissions,
            'tab' => $tab,
            'counts' => (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
