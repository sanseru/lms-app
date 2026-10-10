<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = userWithRole('admin');
});

test('admin can create a student linked to a guardian', function () {
    $guardian = userWithRole('guardian');

    $this->actingAs($this->admin)->post('/people/students', [
        'name' => 'Aldi Saputra',
        'username' => 'Aldi',
        'status' => 'active',
        'password' => 'rahasia123',
        'grade' => '5 SD',
        'guardian_ids' => [$guardian->id],
    ])->assertRedirect(route('people.students.index'));

    $student = User::where('username', 'aldi')->firstOrFail();

    expect($student->hasRole('student'))->toBeTrue()
        ->and($student->studentProfile->student_code)->toBe('SIS-00001')
        ->and($student->studentProfile->grade)->toBe('5 SD')
        ->and($student->guardians->pluck('id')->all())->toBe([$guardian->id]);
});

test('student needs a username or email', function () {
    $this->actingAs($this->admin)
        ->post('/people/students', ['name' => 'Tanpa Login', 'status' => 'active', 'password' => 'rahasia123'])
        ->assertSessionHasErrors('username');
});

test('non-guardian ids are ignored when linking guardians', function () {
    $teacher = userWithRole('teacher');

    $this->actingAs($this->admin)->post('/people/students', [
        'name' => 'Nadia', 'username' => 'nadia', 'status' => 'active', 'password' => 'rahasia123',
        'guardian_ids' => [$teacher->id],
    ]);

    expect(User::where('username', 'nadia')->first()->guardians)->toBeEmpty();
});

test('admin can update a student without changing the password', function () {
    $this->actingAs($this->admin)->post('/people/students', [
        'name' => 'Raka', 'username' => 'raka', 'status' => 'active', 'password' => 'rahasia123',
    ]);
    $student = User::where('username', 'raka')->first();
    $hash = $student->password;

    $this->actingAs($this->admin)->put("/people/students/{$student->id}", [
        'name' => 'Raka Aditya', 'username' => 'raka', 'status' => 'inactive', 'password' => '',
    ])->assertRedirect();

    $student->refresh();
    expect($student->name)->toBe('Raka Aditya')
        ->and($student->status)->toBe('inactive')
        ->and($student->password)->toBe($hash);
});

test('student routes reject users with other roles', function () {
    $teacher = userWithRole('teacher');

    $this->actingAs($this->admin)->get("/people/students/{$teacher->id}/edit")->assertNotFound();
});

test('admin can create a teacher and a guardian with children', function () {
    $this->actingAs($this->admin)->post('/people/teachers', [
        'name' => 'Budi', 'email' => 'budi@example.test', 'status' => 'active', 'password' => 'rahasia123', 'expertise' => 'Python', 'rate_per_session' => 100000,
    ])->assertRedirect(route('people.teachers.index'));

    $child = userWithRole('student');
    $child->studentProfile()->create(['student_code' => 'SIS-00099']);

    $this->actingAs($this->admin)->post('/people/guardians', [
        'name' => 'Ibu Sari', 'username' => 'ibusari', 'phone' => '08123', 'status' => 'active', 'password' => 'rahasia123',
        'relationship' => 'ibu', 'student_ids' => [$child->id],
    ])->assertRedirect(route('people.guardians.index'));

    expect(User::where('email', 'budi@example.test')->first()->teacherProfile->employee_code)->toBe('GRU-0001')
        ->and(User::where('username', 'ibusari')->first()->children->pluck('id')->all())->toBe([$child->id]);
});

test('students list can be searched', function () {
    $this->actingAs($this->admin)->post('/people/students', ['name' => 'Kirana Ayu', 'username' => 'kirana', 'status' => 'active', 'password' => 'rahasia123']);
    $this->actingAs($this->admin)->post('/people/students', ['name' => 'Bima Sakti', 'username' => 'bima', 'status' => 'active', 'password' => 'rahasia123']);

    $this->actingAs($this->admin)->get('/people/students?search=kira')
        ->assertInertia(fn ($page) => $page
            ->component('People::Students/Index')
            ->has('students.data', 1)
            ->where('students.data.0.name', 'Kirana Ayu'));
});
