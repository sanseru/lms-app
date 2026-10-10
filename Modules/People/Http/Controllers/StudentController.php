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
use Modules\People\Http\Requests\StudentRequest;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'status');

        $students = User::role(Role::Student->value)
            ->with(['studentProfile', 'guardians:id,name,phone'])
            ->withCount('enrolledClasses')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhereHas('studentProfile', fn ($q) => $q->where('student_code', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'initials' => $user->initials(),
                'username' => $user->username,
                'email' => $user->email,
                'status' => $user->status,
                'code' => $user->studentProfile?->student_code,
                'grade' => $user->studentProfile?->grade,
                'school' => $user->studentProfile?->school,
                'classes_count' => $user->enrolled_classes_count,
                'guardians' => $user->guardians->map->only('id', 'name', 'phone'),
            ]);

        return Inertia::render('People::Students/Index', [
            'students' => $students,
            'filters' => $filters,
            'stats' => [
                'total' => User::role(Role::Student->value)->count(),
                'active' => User::role(Role::Student->value)->where('status', 'active')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('People::Students/Form', [
            'student' => null,
            'guardians' => $this->guardianOptions(),
        ]);
    }

    public function store(StudentRequest $request, SavePerson $save): RedirectResponse
    {
        $student = $save->student($request->validated());

        return to_route('people.students.index')->with('success', "Siswa {$student->name} berhasil ditambahkan.");
    }

    public function edit(User $student): Response
    {
        abort_unless($student->hasRole(Role::Student->value), 404);
        $student->load(['studentProfile', 'guardians:id']);
        $profile = $student->studentProfile;

        return Inertia::render('People::Students/Form', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'username' => $student->username,
                'email' => $student->email,
                'phone' => $student->phone,
                'status' => $student->status,
                'code' => $profile?->student_code,
                'birth_date' => $profile?->birth_date?->toDateString(),
                'gender' => $profile?->gender,
                'school' => $profile?->school,
                'grade' => $profile?->grade,
                'address' => $profile?->address,
                'notes' => $profile?->notes,
                'joined_at' => $profile?->joined_at?->toDateString(),
                'guardian_ids' => $student->guardians->pluck('id'),
            ],
            'guardians' => $this->guardianOptions(),
        ]);
    }

    public function update(StudentRequest $request, User $student, SavePerson $save): RedirectResponse
    {
        abort_unless($student->hasRole(Role::Student->value), 404);
        $save->student($request->validated(), $student);

        return to_route('people.students.index')->with('success', "Data {$student->name} berhasil diperbarui.");
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->hasRole(Role::Student->value), 404);
        $student->delete();

        return back()->with('success', "Siswa {$student->name} dihapus.");
    }

    private function guardianOptions()
    {
        return User::role(Role::Guardian->value)->orderBy('name')->get(['id', 'name', 'phone']);
    }
}
