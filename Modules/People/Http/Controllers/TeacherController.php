<?php

namespace Modules\People\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Enums\Role;
use Modules\People\Actions\SavePerson;
use Modules\People\Http\Requests\TeacherRequest;

class TeacherController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'status');

        $teachers = User::role(Role::Teacher->value)
            ->with('teacherProfile')
            ->withCount('teachingClasses')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")
                ->orWhereHas('teacherProfile', fn ($q) => $q->whereLike('expertise', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'initials' => $user->initials(),
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'code' => $user->teacherProfile?->employee_code,
                'expertise' => $user->teacherProfile?->expertise,
                'rate_per_session' => $user->teacherProfile?->rate_per_session ?? 0,
                'classes_count' => $user->teaching_classes_count,
            ]);

        return Inertia::render('People::Teachers/Index', [
            'teachers' => $teachers,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('People::Teachers/Form', ['teacher' => null]);
    }

    public function store(TeacherRequest $request, SavePerson $save): RedirectResponse
    {
        $teacher = $save->teacher($request->validated());

        return to_route('people.teachers.index')->with('success', "Teacher {$teacher->name} added.");
    }

    public function edit(User $teacher): Response
    {
        abort_unless($teacher->hasRole(Role::Teacher->value), 404);
        $profile = $teacher->teacherProfile;

        return Inertia::render('People::Teachers/Form', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'username' => $teacher->username,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'status' => $teacher->status,
                'code' => $profile?->employee_code,
                'expertise' => $profile?->expertise,
                'rate_per_session' => $profile?->rate_per_session ?? 0,
                'bank_name' => $profile?->bank_name,
                'bank_account' => $profile?->bank_account,
                'bio' => $profile?->bio,
                'joined_at' => $profile?->joined_at?->toDateString(),
            ],
        ]);
    }

    public function update(TeacherRequest $request, User $teacher, SavePerson $save): RedirectResponse
    {
        abort_unless($teacher->hasRole(Role::Teacher->value), 404);
        $save->teacher($request->validated(), $teacher);

        return to_route('people.teachers.index')->with('success', "{$teacher->name}'s details updated.");
    }

    public function destroy(User $teacher): RedirectResponse
    {
        abort_unless($teacher->hasRole(Role::Teacher->value), 404);

        if ($teacher->teachingClasses()->where('status', 'active')->exists()) {
            return back()->with('error', 'This teacher still teaches active classes. Reassign those classes first.');
        }

        $teacher->delete();

        return back()->with('success', "Teacher {$teacher->name} deleted.");
    }
}
