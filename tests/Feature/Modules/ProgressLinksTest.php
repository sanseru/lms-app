<?php

/** Teachers reach a student's learning progress from their class page (task: teacher link to progress). */
test('the class teacher and admins get progress links on the class page, other teachers do not', function () {
    [$teacher, $student, , $class] = learningSetup();

    $this->actingAs($teacher)->get("/classes/{$class->id}")
        ->assertInertia(fn ($p) => $p->where('can.progress', true)->where('students.0.id', $student->id));
    $this->actingAs(userWithRole('admin'))->get("/classes/{$class->id}")
        ->assertInertia(fn ($p) => $p->where('can.progress', true));

    // The linked report itself: own class allowed, another teacher's student forbidden.
    $this->actingAs($teacher)->get("/progress/{$student->id}")->assertOk();
    [$otherTeacher] = learningSetup();
    $this->actingAs($otherTeacher)->get("/progress/{$student->id}")->assertForbidden();
});

test('the monthly reports list links each student to the progress report', function () {
    [$teacher, $student] = learningSetup();

    $this->actingAs($teacher)->get('/monthly-reports')
        ->assertInertia(fn ($p) => $p->where('students.data.0.id', $student->id));
});
