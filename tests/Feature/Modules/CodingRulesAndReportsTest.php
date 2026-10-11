<?php

use Modules\Coding\Grading\CodeRules;
use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeSubmission;
use Modules\Learning\Models\LessonReport;

test('code rules ignore comments and strings and match whole words', function () {
    $rules = new CodeRules;
    $code = "# pakai for di sini\nangka = [1, 2]\nprint(\"sum\")\ntotal = 0\nfor a in angka:\n    total += a\nprint(total)";
    $result = collect($rules->check([
        ['type' => 'require', 'value' => 'for'],
        ['type' => 'forbid', 'value' => 'sum'],
        ['type' => 'require', 'value' => 'while', 'message' => 'Use a while loop'],
        ['type' => 'max_lines', 'value' => '5'],
    ], 'python', $code));

    expect($result->pluck('passed')->all())->toBe([true, true, false, false])
        ->and($result[2]['message'])->toBe('Use a while loop')
        ->and($result[3]['message'])->toBe('Your code has 6 lines.');

    expect($rules->check([['type' => 'require', 'value' => 'for']], 'python', '# for\nprint(format(1))')[0]['passed'])->toBeFalse()
        ->and($rules->check([['type' => 'forbid', 'value' => 'reduce']], 'javascript', "// reduce\nconst s = `reduce`;")[0]['passed'])->toBeTrue();
});

test('rules are part of grading and of the solution check', function () {
    [, $student, $course] = codingSetup();
    $lesson = ioExercise($course);
    $exercise = CodeExercise::first();
    $exercise->update(['rules' => [['type' => 'forbid', 'value' => '**', 'message' => 'Multiply instead of using **']]]);
    $exercise->update(['verified_hash' => $exercise->contentHash()]);
    [$visible, $hidden] = testIds($lesson);

    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", [
        'code' => "s = float(input())\nprint(s ** 2)", 'outputs' => [$visible => ['output' => '16.0'], $hidden => ['output' => '2.25']],
    ]);

    $submission = CodeSubmission::firstOrFail();
    expect($submission->status)->toBe('failed')
        ->and($submission->total_tests)->toBe(3)
        ->and(collect($submission->results)->firstWhere('id', 'rule-0')['message'])->toBe('Multiply instead of using **');
});

test('changing a graded exercise creates a new version and keeps old scores', function () {
    [$teacher, $student, $course] = codingSetup();
    $lesson = ioExercise($course);
    $fresh = CodeExercise::first();
    $fresh->update(['verified_hash' => $fresh->contentHash()]);
    [$visible, $hidden] = testIds($lesson);
    $payload = fn (array $extra = []) => $extra + [
        'title' => 'Luas persegi', 'language' => 'python', 'mode' => 'io', 'solution_code' => "s = int(input())\nprint(s * s)", 'time_limit_ms' => 2000, 'is_published' => true,
        'tests' => [['id' => $visible, 'kind' => 'io', 'input' => '4', 'expected' => '16', 'weight' => 1], ['id' => $hidden, 'kind' => 'io', 'input' => '1.5', 'expected' => '2.25', 'is_hidden' => true, 'weight' => 1]],
    ];

    // Belum ada yang mengumpulkan: perubahan tetap di versi 1.
    $this->actingAs($teacher)->put("/lessons/{$lesson->id}/code", $payload())->assertSessionHasNoErrors();
    expect(CodeExercise::first()->version)->toBe(1)->and(CodeExercise::first()->isVerified())->toBeTrue();

    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", [
        'code' => "s = float(input())\nprint(s * s)", 'outputs' => [$visible => ['output' => '16.0'], $hidden => ['output' => '2.25']],
    ]);
    expect(CodeSubmission::first())->exercise_version->toBe(1)->status->toBe('passed');

    $this->actingAs($teacher)->put("/lessons/{$lesson->id}/code", $payload(['rules' => [['type' => 'forbid', 'value' => '**']]]));
    $this->actingAs($teacher)->post("/lessons/{$lesson->id}/code/verify", ['outputs' => [$visible => ['output' => '16'], $hidden => ['output' => '2.25']], 'publish' => true])
        ->assertSessionHas('success');

    $exercise = CodeExercise::first();
    expect($exercise->version)->toBe(2)
        ->and($exercise->versions()->pluck('version')->all())->toBe([2, 1])
        ->and($exercise->versions()->where('version', 1)->first()->snapshot['rules'])->toBe([])
        ->and(CodeSubmission::first()->status)->toBe('passed');

    $this->actingAs($student)->get("/lessons/{$lesson->id}")
        ->assertInertia(fn ($page) => $page->where('exercise.version', 2)->where('history.0.version', 1)->where('exercise.rules.0.label', 'Does not use `**`'));
});

test('students report a problem and the teacher closes it', function () {
    [$teacher, $student, $course] = codingSetup();
    $lesson = ioExercise($course);

    $this->actingAs($student)->post("/lessons/{$lesson->id}/report", ['reason' => 'wrong_answer', 'message' => 'Test 1 expects 16 but I think it is 15'])
        ->assertSessionHas('success');
    $this->actingAs($student)->post("/lessons/{$lesson->id}/report", ['reason' => 'unclear']);

    expect(LessonReport::count())->toBe(1)
        ->and($teacher->notifications()->first()->data['title'])->toBe('Problem reported: Luas persegi');

    $this->actingAs($teacher)->get("/lessons/{$lesson->id}")->assertInertia(fn ($page) => $page->where('reports.open', 1));
    $this->actingAs($student)->get("/lessons/{$lesson->id}")->assertInertia(fn ($page) => $page->where('reports.mine', true));
    $this->actingAs($teacher)->get('/lesson-reports')->assertInertia(fn ($page) => $page->component('Learning::Reports/Index')->has('reports.data', 1));

    $report = LessonReport::first();
    $this->actingAs($student)->post("/lesson-reports/{$report->id}/close", ['status' => 'resolved'])->assertForbidden();
    $this->actingAs($teacher)->post("/lesson-reports/{$report->id}/close", ['status' => 'dismissed', 'response' => '4 × 4 is 16, so the test is right.'])
        ->assertSessionHas('success');

    expect($report->fresh()->status)->toBe('dismissed')
        ->and($student->notifications()->first()->data['body'])->toBe('4 × 4 is 16, so the test is right.');
});

test('only enrolled students can report a lesson', function () {
    [, , $course] = codingSetup();
    $lesson = ioExercise($course);

    $this->actingAs(userWithRole('student'))->post("/lessons/{$lesson->id}/report", ['reason' => 'other'])->assertForbidden();
    $this->actingAs(userWithRole('guardian'))->post("/lessons/{$lesson->id}/report", ['reason' => 'other'])->assertForbidden();
});
