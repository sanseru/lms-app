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
use Modules\People\Http\Requests\GuardianRequest;

class GuardianController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search');

        $guardians = User::role(Role::Guardian->value)
            ->with('children:id,name')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhereHas('children', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
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
                'relationship' => $user->children->first()?->pivot->relationship,
                'children' => $user->children->map->only('id', 'name'),
            ]);

        return Inertia::render('People::Guardians/Index', [
            'guardians' => $guardians,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('People::Guardians/Form', [
            'guardian' => null,
            'students' => $this->studentOptions(),
        ]);
    }

    public function store(GuardianRequest $request, SavePerson $save): RedirectResponse
    {
        $guardian = $save->guardian($request->validated());

        return to_route('people.guardians.index')->with('success', "Orang tua {$guardian->name} berhasil ditambahkan.");
    }

    public function edit(User $guardian): Response
    {
        abort_unless($guardian->hasRole(Role::Guardian->value), 404);
        $guardian->load('children:id');

        return Inertia::render('People::Guardians/Form', [
            'guardian' => [
                'id' => $guardian->id,
                'name' => $guardian->name,
                'username' => $guardian->username,
                'email' => $guardian->email,
                'phone' => $guardian->phone,
                'status' => $guardian->status,
                'relationship' => $guardian->children->first()?->pivot->relationship ?? 'ibu',
                'student_ids' => $guardian->children->pluck('id'),
            ],
            'students' => $this->studentOptions(),
        ]);
    }

    public function update(GuardianRequest $request, User $guardian, SavePerson $save): RedirectResponse
    {
        abort_unless($guardian->hasRole(Role::Guardian->value), 404);
        $save->guardian($request->validated(), $guardian);

        return to_route('people.guardians.index')->with('success', "Data {$guardian->name} berhasil diperbarui.");
    }

    public function destroy(User $guardian): RedirectResponse
    {
        abort_unless($guardian->hasRole(Role::Guardian->value), 404);
        $guardian->children()->detach();
        $guardian->delete();

        return back()->with('success', "Orang tua {$guardian->name} dihapus.");
    }

    private function studentOptions()
    {
        return User::role(Role::Student->value)
            ->with('studentProfile:id,user_id,student_code')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'code' => $user->studentProfile?->student_code]);
    }
}
