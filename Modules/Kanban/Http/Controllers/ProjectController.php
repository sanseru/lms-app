<?php

namespace Modules\Kanban\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Kanban\Actions\SyncBoards;
use Modules\Kanban\Http\Requests\ProjectRequest;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Card;
use Modules\Kanban\Models\Project;

class ProjectController extends Controller
{
    public function __construct(private SyncBoards $sync) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $projects = Project::query()->visibleTo($user)->with(['classRoom', 'templateTasks'])
            ->orderByRaw("status = 'archived'")->orderByRaw('due_date is null')->orderBy('due_date')->latest('id')
            ->get();

        if ($user->can('kanban.manage')) {
            $projects->load('boards.cards:id,board_id,column');

            return Inertia::render('Kanban::Projects/Index', [
                'mode' => 'staff',
                'projects' => $projects->map(fn (Project $p) => $p->toCard() + [
                    'students' => $p->boards->count(),
                    'completed' => $p->boards->whereNotNull('completed_at')->count(),
                    'percent' => (int) round($p->boards->avg(fn (Board $b) => $b->progress()['percent']) ?? 0),
                    'to_review' => $p->boards->sum(fn (Board $b) => $b->cards->where('column', 'review')->count()),
                ]),
            ]);
        }

        $students = $user->hasRole('guardian') ? $user->children : collect([$user]);
        $items = $projects->flatMap(fn (Project $p) => $students
            ->filter(fn (User $s) => $p->classRoom->students()->whereKey($s->id)->exists())
            ->map(fn (User $s) => $p->toCard() + ['board' => $this->sync->boardFor($p, $s)->load('student', 'cards')->toSummary()]));

        return Inertia::render('Kanban::Projects/Index', [
            'mode' => $user->hasRole('guardian') ? 'guardian' : 'student',
            'projects' => $items->values(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Project::class);

        return Inertia::render('Kanban::Projects/Form', [
            'project' => null,
            'classes' => $this->classOptions($request->user()),
            'defaultClass' => $request->integer('class') ?: null,
        ]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $project = Project::create($request->safe()->except('tasks') + [
                'created_by' => $request->user()->id,
                'requires_review' => $request->boolean('requires_review'),
            ]);
            foreach (array_values($request->validated('tasks', [])) as $i => $task) {
                $project->templateTasks()->create(['title' => $task['title'], 'description' => $task['description'] ?? null, 'position' => $i + 1]);
            }

            return $project;
        });

        $this->sync->handle($project->load('templateTasks'));

        return to_route('kanban.projects.show', $project)->with('success', 'Project created. Every student now has their own board.');
    }

    public function show(Request $request, Project $project): Response|RedirectResponse
    {
        Gate::authorize('view', $project);
        $user = $request->user();

        if (! $project->isStaff($user)) {
            abort_unless($user->hasRole('student'), 403);

            return to_route('kanban.boards.show', $this->sync->boardFor($project, $user));
        }

        $this->sync->handle($project);
        $project->load(['classRoom', 'templateTasks', 'boards.student', 'boards.cards.reviewer']);
        $boards = $project->boards->sortBy('student.name')->values();

        return Inertia::render('Kanban::Projects/Show', [
            'project' => $project->toCard() + ['tasks' => $project->templateTasks->map->only('id', 'title', 'description')],
            'boards' => $boards->map->toSummary(),
            'reviews' => $boards->flatMap(fn (Board $b) => $b->cards->where('column', 'review')
                ->map(fn (Card $c) => $c->toItem() + ['board_id' => $b->id, 'student' => $b->student->name, 'updated_at' => $c->updated_at->diffForHumans()]))
                ->values(),
            'columns' => Card::COLUMNS,
        ]);
    }

    public function edit(Request $request, Project $project): Response
    {
        Gate::authorize('manage', $project);

        return Inertia::render('Kanban::Projects/Form', [
            'project' => $project->only('id', 'class_room_id', 'title', 'description', 'emoji', 'requires_review', 'status') + [
                'due_date' => $project->due_date?->toDateString(),
                'tasks' => $project->templateTasks->map->only('id', 'title', 'description'),
            ],
            'classes' => $this->classOptions($request->user()),
            'defaultClass' => null,
        ]);
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        DB::transaction(function () use ($request, $project) {
            $project->update($request->safe()->except('tasks', 'class_room_id') + ['requires_review' => $request->boolean('requires_review')]);

            $keep = [];
            foreach (array_values($request->validated('tasks', [])) as $i => $data) {
                $task = $project->templateTasks()->find($data['id'] ?? 0) ?? $project->templateTasks()->make();
                $task->fill(['title' => $data['title'], 'description' => $data['description'] ?? null, 'position' => $i + 1])->save();
                $keep[] = $task->id;

                // Title changes carry over to students' cards that are not done yet.
                Card::where('template_task_id', $task->id)->where('column', '!=', 'done')
                    ->update(['title' => $task->title, 'description' => $task->description]);
            }

            $removed = $project->templateTasks()->whereNotIn('id', $keep)->pluck('id');
            Card::whereIn('template_task_id', $removed)->where('column', 'todo')->delete();
            $project->templateTasks()->whereIn('id', $removed)->delete();
        });

        $this->sync->handle($project->fresh('templateTasks'));
        $project->boards->each->refreshCompletion();

        return to_route('kanban.projects.show', $project)->with('success', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('manage', $project);
        $project->delete();

        return to_route('kanban.projects.index')->with('success', 'Project deleted.');
    }

    private function classOptions(User $user)
    {
        return ClassRoom::query()
            ->when(! $user->hasRole('admin'), fn ($q) => $q->where('teacher_id', $user->id))
            ->where('status', 'active')->orderBy('name')->get(['id', 'name', 'color']);
    }
}
