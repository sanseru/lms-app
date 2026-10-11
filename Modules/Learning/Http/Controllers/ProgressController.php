<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Learning\Actions\StudentProgress;

/** Per-student learning report for parents, teachers and admins. */
class ProgressController extends Controller
{
    /** Parents: go straight to the child if there is only one, otherwise pick a child. */
    public function index(Request $request): Response|RedirectResponse
    {
        $children = $request->user()->children()->orderBy('name')->get();

        if ($children->count() === 1) {
            return to_route('learning.progress.show', $children->first());
        }

        return Inertia::render('Learning::Progress/Index', [
            'children' => $children->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'initials' => $c->initials()]),
        ]);
    }

    public function show(Request $request, User $student, StudentProgress $progress): Response
    {
        $user = $request->user();
        abort_unless($student->hasRole('student'), 404);
        abort_unless(
            $user->id === $student->id
            || $user->can('learning.manage') && ($user->hasRole('admin') || $student->enrolledClasses()->where('teacher_id', $user->id)->exists())
            || $user->children()->whereKey($student->id)->exists(),
            403,
        );

        $student->loadMissing('studentProfile');

        return Inertia::render('Learning::Progress/Show', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'initials' => $student->initials(),
                'code' => $student->studentProfile?->student_code,
            ],
            'courses' => $progress->forStudent($student),
            'siblings' => $user->hasRole('guardian') ? $user->children()->orderBy('name')->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]) : [],
        ]);
    }
}
