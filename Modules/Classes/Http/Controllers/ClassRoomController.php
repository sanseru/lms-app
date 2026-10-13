<?php

namespace Modules\Classes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Http\Requests\ClassRoomRequest;
use Modules\Classes\Models\ClassRoom;
use Modules\Identity\Enums\Role;

class ClassRoomController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'status', 'subject');
        $user = $request->user();

        $classes = ClassRoom::query()
            ->visibleTo($user)
            ->with('teacher:id,name')
            ->withCount('students')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('code', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['subject'] ?? null, fn ($q, $subject) => $q->where('subject', $subject))
            ->orderByRaw("case status when 'active' then 0 when 'upcoming' then 1 else 2 end")
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ClassRoom $class) => $class->toCard());

        return Inertia::render('Classes::Classes/Index', [
            'classes' => $classes,
            'filters' => $filters,
            'subjects' => ClassRoom::query()->visibleTo($user)->distinct()->orderBy('subject')->pluck('subject'),
            'statuses' => ClassRoom::STATUSES,
            'can' => ['manage' => $user->can('classes.manage')],
        ]);
    }

    public function show(Request $request, ClassRoom $class): Response
    {
        Gate::authorize('view', $class);
        $user = $request->user();
        $class->load(['teacher.teacherProfile', 'students.studentProfile'])->loadCount('students');
        $canRoster = $user->can('viewRoster', $class);

        $enrolledIds = $class->students->pluck('id');

        return Inertia::render('Classes::Classes/Show', [
            'classRoom' => [
                ...$class->toCard(),
                'description' => $class->description,
                'zoom_meeting_id' => $class->zoom_meeting_id,
                'zoom_passcode' => $class->zoom_passcode,
                'start_date' => $class->start_date?->translatedFormat('d M Y'),
                'end_date' => $class->end_date?->translatedFormat('d M Y'),
                'teacher' => $class->teacher ? [
                    'name' => $class->teacher->name,
                    'initials' => $class->teacher->initials(),
                    'expertise' => $class->teacher->teacherProfile?->expertise,
                    'phone' => $canRoster ? $class->teacher->phone : null,
                ] : null,
            ],
            'students' => $canRoster
                ? $class->students->sortBy('name')->values()->map(fn (User $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'initials' => $s->initials(),
                    'code' => $s->studentProfile?->student_code,
                    'grade' => $s->studentProfile?->grade,
                    'joined_at' => $s->pivot->joined_at ? Carbon::parse($s->pivot->joined_at)->translatedFormat('d M Y') : null,
                ])
                : [],
            'availableStudents' => $user->can('classes.manage')
                ? User::role(Role::Student->value)->where('status', 'active')->whereNotIn('id', $enrolledIds)->orderBy('name')->get(['id', 'name'])
                : [],
            'sessions' => $class->sessions()
                ->with(['classRoom:id,name,subject,color,zoom_url', 'teacher:id,name'])
                ->where('starts_at', '>=', now()->subWeeks(2))
                ->orderBy('starts_at')
                ->take(8)
                ->get()
                ->map->toItem(),
            'can' => [
                'manage' => $user->can('update', $class),
                'roster' => $canRoster,
                // Open lessons early for this class (Learning module): its teacher or an admin.
                'lessonAccess' => $class->course_id && ($user->hasRole('admin') || $class->teacher_id === $user->id),
                // Learning progress report per student: the class teacher or an admin (same rule as ProgressController).
                'progress' => $user->can('learning.manage') && ($user->hasRole('admin') || $class->teacher_id === $user->id),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Classes::Classes/Form', $this->formProps(null));
    }

    public function store(ClassRoomRequest $request): RedirectResponse
    {
        $class = ClassRoom::create($request->validated() + [
            'code' => ClassRoom::nextCode($request->input('subject')),
        ]);

        return to_route('classes.show', $class)->with('success', "Class {$class->name} created. Now add students to it.");
    }

    public function edit(ClassRoom $class): Response
    {
        Gate::authorize('update', $class);

        return Inertia::render('Classes::Classes/Form', $this->formProps($class));
    }

    public function update(ClassRoomRequest $request, ClassRoom $class): RedirectResponse
    {
        $class->update($request->validated());

        return to_route('classes.show', $class)->with('success', 'Class updated.');
    }

    public function destroy(ClassRoom $class): RedirectResponse
    {
        Gate::authorize('delete', $class);
        $class->delete();

        return to_route('classes.index')->with('success', "Class {$class->name} deleted.");
    }

    public function enroll(Request $request, ClassRoom $class): RedirectResponse
    {
        Gate::authorize('update', $class);

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('student_profiles', 'user_id')],
        ], [], ['student_ids' => 'students']);

        $current = $class->students()->count();
        $new = collect($data['student_ids'])->diff($class->students()->pluck('users.id'));

        if ($class->capacity && $current + $new->count() > $class->capacity) {
            return back()->with('error', "This would exceed the class capacity of {$class->capacity} students.");
        }

        // Check for schedule clashes with other classes the students attend.
        $clashes = ClassRoom::query()
            ->whereKeyNot($class->id)
            ->whereIn('status', ['active', 'upcoming'])
            ->where('start_time', '<', $class->end_time)
            ->where('end_time', '>', $class->start_time)
            ->with(['students' => fn ($q) => $q->whereIn('users.id', $new)])
            ->get()
            ->filter(fn (ClassRoom $other) => array_intersect($other->schedule_days, $class->schedule_days) !== [])
            ->flatMap(fn (ClassRoom $other) => $other->students->map(fn ($s) => "{$s->name} ({$other->name})"));

        if ($clashes->isNotEmpty()) {
            return back()->with('error', 'Schedule clash: '.$clashes->unique()->implode(', ').'.');
        }

        $class->students()->syncWithoutDetaching(
            $new->mapWithKeys(fn ($id) => [$id => ['status' => 'active', 'joined_at' => now()]])->all()
        );

        return back()->with('success', "{$new->count()} student(s) added to the class.");
    }

    public function unenroll(ClassRoom $class, User $student): RedirectResponse
    {
        Gate::authorize('update', $class);
        $class->students()->detach($student->id);

        return back()->with('success', "{$student->name} removed from the class.");
    }

    private function formProps(?ClassRoom $class): array
    {
        return [
            'classRoom' => $class ? [
                ...$class->only(['id', 'code', 'name', 'subject', 'level', 'description', 'teacher_id', 'zoom_url', 'zoom_meeting_id', 'zoom_passcode', 'schedule_days', 'capacity', 'status', 'color']),
                'start_time' => substr($class->start_time, 0, 5),
                'end_time' => substr($class->end_time, 0, 5),
                'start_date' => $class->start_date?->toDateString(),
                'end_date' => $class->end_date?->toDateString(),
            ] : null,
            'teachers' => User::role(Role::Teacher->value)->where('status', 'active')->with('teacherProfile:id,user_id,expertise')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'expertise' => $t->teacherProfile?->expertise]),
            'options' => [
                'days' => ClassRoom::DAYS,
                'levels' => ClassRoom::LEVELS,
                'statuses' => ClassRoom::STATUSES,
                'colors' => ClassRoom::COLORS,
                'subjects' => ClassRoom::query()->distinct()->orderBy('subject')->pluck('subject')
                    ->merge(['Python', 'Scratch', 'Web Development', 'Roblox', 'Minecraft Education', 'Artificial Intelligence', 'Robotics'])
                    ->unique()->values(),
            ],
        ];
    }
}
