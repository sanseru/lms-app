<?php

use Modules\Classes\Models\ClassRoom;

function makeClass(array $attributes = []): ClassRoom
{
    return ClassRoom::create($attributes + [
        'code' => 'PYT-'.fake()->unique()->numberBetween(100, 999),
        'name' => 'Python Start 1',
        'subject' => 'Python',
        'level' => 'beginner',
        'schedule_days' => [2, 4],
        'start_time' => '16:00',
        'end_time' => '17:30',
        'status' => 'active',
        'color' => 'violet',
    ]);
}

function classPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Scratch Game Maker',
        'subject' => 'Scratch',
        'level' => 'beginner',
        'teacher_id' => null,
        'zoom_url' => 'https://zoom.us/j/123456789',
        'schedule_days' => [3, 6],
        'start_time' => '09:00',
        'end_time' => '10:30',
        'status' => 'active',
        'color' => 'amber',
    ];
}

test('admin can create a class with a zoom link', function () {
    $admin = userWithRole('admin');
    $teacher = userWithRole('teacher');

    $this->actingAs($admin)->post('/classes', classPayload(['teacher_id' => $teacher->id]))->assertRedirect();

    $class = ClassRoom::firstOrFail();
    expect($class->code)->toBe('SCR-001')
        ->and($class->teacher_id)->toBe($teacher->id)
        ->and($class->schedule_days)->toBe([3, 6])
        ->and($class->zoom_url)->toBe('https://zoom.us/j/123456789');
});

test('class creation rejects a teacher schedule conflict', function () {
    $admin = userWithRole('admin');
    $teacher = userWithRole('teacher');
    makeClass(['teacher_id' => $teacher->id, 'schedule_days' => [6], 'start_time' => '09:30', 'end_time' => '11:00']);

    $this->actingAs($admin)->post('/classes', classPayload(['teacher_id' => $teacher->id]))
        ->assertSessionHasErrors('teacher_id');

    // Jam yang bersebelahan tidak dianggap bentrok.
    $this->actingAs($admin)->post('/classes', classPayload(['teacher_id' => $teacher->id, 'start_time' => '08:00', 'end_time' => '09:30']))
        ->assertSessionHasNoErrors();
});

test('class teacher must actually be a teacher', function () {
    $this->actingAs(userWithRole('admin'))
        ->post('/classes', classPayload(['teacher_id' => userWithRole('student')->id]))
        ->assertSessionHasErrors('teacher_id');
});

test('admin can enroll students up to capacity', function () {
    $admin = userWithRole('admin');
    $class = makeClass(['capacity' => 1]);
    [$a, $b] = [userWithRole('student'), userWithRole('student')];
    $a->studentProfile()->create(['student_code' => 'SIS-1']);
    $b->studentProfile()->create(['student_code' => 'SIS-2']);

    $this->actingAs($admin)->post("/classes/{$class->id}/students", ['student_ids' => [$a->id]])->assertSessionHas('success');
    $this->actingAs($admin)->post("/classes/{$class->id}/students", ['student_ids' => [$b->id]])->assertSessionHas('error');

    expect($class->students()->pluck('users.id')->all())->toBe([$a->id]);
});

test('students, teachers and guardians only see their own classes', function () {
    $teacher = userWithRole('teacher');
    $student = userWithRole('student');
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($student->id);

    $mine = makeClass(['teacher_id' => $teacher->id]);
    $other = makeClass(['name' => 'Kelas lain']);
    $mine->students()->attach($student->id);

    foreach ([$teacher, $student, $guardian] as $user) {
        $this->actingAs($user)->get('/classes')
            ->assertInertia(fn ($page) => $page->has('classes.data', 1)->where('classes.data.0.id', $mine->id));
        $this->actingAs($user)->get("/classes/{$mine->id}")->assertOk();
        $this->actingAs($user)->get("/classes/{$other->id}")->assertForbidden();
    }
});

test('only the class teacher sees the roster', function () {
    $teacher = userWithRole('teacher');
    $student = userWithRole('student');
    $class = makeClass(['teacher_id' => $teacher->id]);
    $class->students()->attach($student->id);

    $this->actingAs($teacher)->get("/classes/{$class->id}")->assertInertia(fn ($page) => $page->has('students', 1));
    $this->actingAs($student)->get("/classes/{$class->id}")->assertInertia(fn ($page) => $page->has('students', 0));
});

test('weekly schedule places classes on their days', function () {
    $student = userWithRole('student');
    $class = makeClass(['schedule_days' => [1, 3]]);
    $class->students()->attach($student->id);

    $this->actingAs($student)->get('/schedule?week=2026-10-05')
        ->assertInertia(fn ($page) => $page
            ->component('Classes::Schedule/Index')
            ->where('days.0.date', '2026-10-05')
            ->has('days.0.sessions', 1)
            ->has('days.1.sessions', 0)
            ->has('days.2.sessions', 1));
});
