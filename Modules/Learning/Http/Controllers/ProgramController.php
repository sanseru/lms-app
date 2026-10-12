<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Http\Requests\ProgramRequest;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Program;

/** Programs group courses into ordered levels, e.g. Python → Python Start 1, Python Start 2, … */
class ProgramController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Learning::Programs/Index', [
            'programs' => Program::query()
                ->with(['courses' => fn ($q) => $q->withCount(['lessons' => fn ($q) => $q->where('is_published', true)])])
                ->orderBy('position')->orderBy('name')
                ->get()
                ->map(fn (Program $p) => $p->toCard() + [
                    'levels' => $p->courses->map(fn (Course $c) => $c->only('id', 'title', 'level_order', 'status') + ['lessons_count' => $c->lessons_count])->values(),
                ]),
            'unassigned' => Course::query()->whereNull('program_id')->orderBy('title')->get(['id', 'title', 'status']),
            'colors' => ClassRoom::COLORS,
        ]);
    }

    public function store(ProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['position'] ??= (int) Program::max('position') + 1;
        Program::create($data);

        return back()->with('success', 'Program created. Now pick it on each course and set its level number.');
    }

    public function update(ProgramRequest $request, Program $program): RedirectResponse
    {
        $data = $request->validated();
        $data['position'] ??= $program->position;
        $program->update($data);

        return back()->with('success', 'Program updated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        // Courses stay; they just no longer belong to a program (the foreign key sets null).
        $program->delete();

        return back()->with('success', 'Program deleted. Its courses are kept.');
    }
}
