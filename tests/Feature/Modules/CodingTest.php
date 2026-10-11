<?php

use Illuminate\Support\Facades\Http;
use Modules\Coding\Grading\CodeRunner;
use Modules\Coding\Grading\Drivers\Judge0Runner;
use Modules\Coding\Grading\Harness;
use Modules\Coding\Grading\OutputComparator;
use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeSubmission;
use Modules\Learning\Actions\StudentProgress;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;

test('output comparison is forgiving about whitespace, case, and decimals', function () {
    $c = new OutputComparator;

    expect($c->text("16\n", '16'))->toBeTrue()
        ->and($c->text('Hello  World', "hello world   \n\n"))->toBeTrue()
        ->and($c->text('Hello', 'hello', caseSensitive: true))->toBeFalse()
        ->and($c->text('0.3', '0.30000000000000004'))->toBeTrue()
        ->and($c->text('2.25', '2'))->toBeFalse()
        ->and($c->text("1\n2", '1 2'))->toBeFalse()
        ->and($c->json('true', 'true'))->toBeTrue()
        ->and($c->json('[1, 2.0]', '[1,2]'))->toBeTrue()
        ->and($c->json('{"a": 1}', '{"a": 2}'))->toBeFalse()
        ->and($c->json('16', 'not json'))->toBeFalse();
});

test('student never receives hidden answers or the solution', function () {
    [, $student, $course] = codingSetup();
    $lesson = ioExercise($course);

    $this->actingAs($student)->get("/lessons/{$lesson->id}")
        ->assertInertia(fn ($page) => $page->component('Coding::Exercises/Show')
            ->where('tests.0.expected', '16')
            ->where('tests.1.expected', null)
            ->where('tests.1.input', '1.5')
            ->missing('tests.1.fail_message')
            ->where('solution', null));
});

test('submission is graded on the server against stored answers', function () {
    [, $student, $course] = codingSetup();
    $lesson = ioExercise($course);
    [$visible, $hidden] = testIds($lesson);

    // Rounding down: the hidden test fails, the teacher's custom message shows, the answer stays secret.
    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", [
        'code' => 'print(int(input()) ** 2)', 'outputs' => [$visible => ['output' => '16'], $hidden => ['output' => '2']],
    ])->assertSessionHas('error');

    $first = CodeSubmission::first();
    expect($first)->status->toBe('failed')->score->toBe(50)->passed_tests->toBe(1)
        ->and($first->results[1])->toMatchArray(['passed' => false, 'hidden' => true, 'expected' => null, 'actual' => null, 'message' => 'The side can be a decimal']);
    expect(LessonProgress::where('student_id', $student->id)->exists())->toBeFalse();

    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", [
        'code' => "s = float(input())\nprint(s * s)", 'outputs' => [$visible => ['output' => "16.0\n"], $hidden => ['output' => '2.25']],
    ])->assertSessionHas('success');

    expect(CodeSubmission::latest('id')->first())->status->toBe('passed')->score->toBe(100);
    expect(LessonProgress::where('student_id', $student->id)->where('lesson_id', $lesson->id)->exists())->toBeTrue();

    // After passing, the solution is visible and the report includes the best score.
    $this->actingAs($student)->get("/lessons/{$lesson->id}")->assertInertia(fn ($page) => $page->whereNot('solution', null)->where('passed', true));
    expect(app(StudentProgress::class)->forStudent($student)->first()['lessons'][0])->toMatchArray(['type' => 'code', 'quiz_best' => 100, 'done' => true]);
});

test('runtime errors and timeouts are reported in friendly words', function () {
    [, $student, $course] = codingSetup();
    $lesson = ioExercise($course);
    [$visible, $hidden] = testIds($lesson);

    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", [
        'code' => 'while True: pass', 'outputs' => [$visible => ['output' => '', 'error' => 'Time limit reached'], $hidden => ['error' => "Line 1: NameError: name 'x' is not defined"]],
    ]);

    $results = CodeSubmission::first()->results;
    expect(CodeSubmission::first()->status)->toBe('error')
        ->and($results[0]['error'])->toContain('loop never stops')
        ->and($results[1]['error'])->toContain('NameError');
});

test('solution unlocks after the configured number of failures', function () {
    [, $student, $course] = codingSetup();
    $lesson = ioExercise($course, ['show_solution_after' => 2]);
    $wrong = ['code' => 'print(0)', 'outputs' => []];

    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", $wrong);
    $this->actingAs($student)->get("/lessons/{$lesson->id}")->assertInertia(fn ($page) => $page->where('solution', null));
    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", $wrong);
    $this->actingAs($student)->get("/lessons/{$lesson->id}")->assertInertia(fn ($page) => $page->whereNot('solution', null));
});

test('function mode compares the returned value', function () {
    [, $student, $course] = codingSetup();
    $lesson = $course->lessons()->create(['title' => 'Even', 'type' => 'code', 'position' => 1, 'is_published' => true]);
    $exercise = CodeExercise::create(['lesson_id' => $lesson->id, 'language' => 'python', 'mode' => 'function', 'function_name' => 'is_even', 'solution_code' => 'def is_even(n): return n % 2 == 0']);
    $a = $exercise->tests()->create(['kind' => 'function', 'input' => '[4]', 'expected' => 'true']);
    $b = $exercise->tests()->create(['kind' => 'function', 'input' => '[3]', 'expected' => 'false', 'is_hidden' => true]);

    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", [
        'code' => 'def is_even(n): return True',
        'outputs' => [$a->id => ['output' => "debug\n__RESULT__true"], $b->id => ['output' => '__RESULT__true']],
    ]);

    expect(CodeSubmission::first())->passed_tests->toBe(1)->status->toBe('failed');
});

test('html exercises re-check structure on the server and take styles from the browser', function () {
    [, $student, $course] = codingSetup();
    $lesson = $course->lessons()->create(['title' => 'Profile', 'type' => 'code', 'position' => 1, 'is_published' => true]);
    $exercise = CodeExercise::create(['lesson_id' => $lesson->id, 'language' => 'html', 'mode' => 'html', 'solution_code' => '<h1>Hi</h1>']);
    $h1 = $exercise->tests()->create(['kind' => 'text', 'input' => 'h1', 'expected' => 'raka']);
    $img = $exercise->tests()->create(['kind' => 'attr', 'input' => 'img', 'property' => 'alt']);
    $bg = $exercise->tests()->create(['kind' => 'style', 'input' => 'body', 'property' => 'background-color', 'expected' => 'lightblue']);

    $html = '<html><body><h1>Hello, I am  Raka</h1><img src="a.png"></body></html>';
    // The browser claims everything passed, but the structure is re-checked on the server.
    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", ['code' => $html, 'styles' => [$h1->id => true, $img->id => true, $bg->id => true]]);

    $results = collect(CodeSubmission::first()->results)->keyBy('id');
    expect($results[$h1->id]['passed'])->toBeTrue()
        ->and($results[$img->id]['passed'])->toBeFalse()
        ->and($results[$bg->id]['passed'])->toBeTrue();
});

test('parsons exercises are graded by line order and shuffled per student', function () {
    [, $student, $course] = codingSetup();
    $lesson = $course->lessons()->create(['title' => 'Rocket', 'type' => 'code', 'position' => 1, 'is_published' => true]);
    CodeExercise::create(['lesson_id' => $lesson->id, 'language' => 'python', 'mode' => 'parsons', 'solution_code' => "for i in range(3):\n    print(i)\n\nprint('Liftoff')"]);

    $this->actingAs($student)->get("/lessons/{$lesson->id}")
        ->assertInertia(fn ($page) => $page->has('exercise.parsons_lines', 3)->where('exercise.parsons_lines', fn ($lines) => collect($lines)->all() !== ['for i in range(3):', '    print(i)', "print('Liftoff')"]));

    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", ['code' => "print('Liftoff')\nfor i in range(3):\n    print(i)"]);
    expect(CodeSubmission::latest('id')->first())->status->toBe('failed')->score->toBe(0);

    // Indentation counts too.
    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", ['code' => "for i in range(3):\nprint(i)\nprint('Liftoff')"]);
    expect(CodeSubmission::latest('id')->first())->score->toBe(67);

    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", ['code' => "for i in range(3):\n    print(i)\nprint('Liftoff')"]);
    expect(CodeSubmission::latest('id')->first())->status->toBe('passed');
});

test('tutor exercises stay draft until the solution passes every test', function () {
    [$teacher, $student, $course] = codingSetup();

    $this->actingAs($teacher)->post("/courses/{$course->id}/code", [
        'title' => 'Area', 'language' => 'python', 'mode' => 'io', 'solution_code' => "s=int(input())\nprint(s*s)", 'time_limit_ms' => 2000, 'is_published' => true,
        'tests' => [['kind' => 'io', 'input' => '3', 'expected' => '9', 'weight' => 1], ['kind' => 'io', 'input' => '5', 'expected' => '25', 'weight' => 1, 'is_hidden' => true]],
    ])->assertRedirectContains('verify=1');

    $lesson = Lesson::where('type', 'code')->first();
    [$t1, $t2] = testIds($lesson);
    expect($lesson->is_published)->toBeFalse();
    $this->actingAs($student)->get("/lessons/{$lesson->id}")->assertForbidden();

    // Solution wrong on one test: not verified.
    $this->actingAs($teacher)->post("/lessons/{$lesson->id}/code/verify", ['outputs' => [$t1 => ['output' => '9'], $t2 => ['output' => '24']], 'publish' => true])
        ->assertSessionHas('error');
    expect($lesson->fresh()->is_published)->toBeFalse();

    $this->actingAs($teacher)->post("/lessons/{$lesson->id}/code/verify", ['outputs' => [$t1 => ['output' => '9'], $t2 => ['output' => '25']], 'publish' => true])
        ->assertSessionHas('success');
    expect($lesson->fresh()->is_published)->toBeTrue();
    $this->actingAs($student)->get("/lessons/{$lesson->id}")->assertOk();

    // Changing tests cancels verification and returns it to draft.
    $this->actingAs($teacher)->put("/lessons/{$lesson->id}/code", [
        'title' => 'Area', 'language' => 'python', 'mode' => 'io', 'solution_code' => "s=int(input())\nprint(s*s)", 'time_limit_ms' => 2000, 'is_published' => true,
        'tests' => [['id' => $t1, 'kind' => 'io', 'input' => '3', 'expected' => '10', 'weight' => 1]],
    ])->assertRedirectContains('verify=1');
    expect($lesson->fresh()->is_published)->toBeFalse();
    expect(CodeExercise::first()->tests()->count())->toBe(1);
});

test('exercise form validates function and html tests', function () {
    [$teacher, , $course] = codingSetup();
    $base = ['title' => 'X', 'solution_code' => 'x', 'time_limit_ms' => 2000];

    $this->actingAs($teacher)->post("/courses/{$course->id}/code", $base + ['language' => 'python', 'mode' => 'function', 'function_name' => '1bad', 'tests' => [['kind' => 'function', 'input' => '4', 'expected' => 'not json', 'weight' => 1]]])
        ->assertSessionHasErrors(['function_name', 'tests.0.input', 'tests.0.expected']);
    $this->actingAs($teacher)->post("/courses/{$course->id}/code", $base + ['language' => 'html', 'mode' => 'io', 'tests' => []])
        ->assertSessionHasErrors('mode');
    $this->actingAs($teacher)->post("/courses/{$course->id}/code", $base + ['language' => 'html', 'mode' => 'html', 'tests' => [['kind' => 'style', 'input' => 'body', 'weight' => 1]]])
        ->assertSessionHasErrors(['tests.0.property', 'tests.0.expected']);
    $this->actingAs(userWithRole('student'))->post("/courses/{$course->id}/code", $base)->assertForbidden();
});

test('only enrolled students can submit', function () {
    [, , $course] = codingSetup();
    $lesson = ioExercise($course);

    $this->actingAs(userWithRole('student'))->post("/lessons/{$lesson->id}/code/submit", ['code' => 'x'])->assertForbidden();
    $this->actingAs(userWithRole('guardian'))->post("/lessons/{$lesson->id}/code/submit", ['code' => 'x'])->assertForbidden();
});

test('judge0 driver runs code remotely and ignores browser outputs', function () {
    config(['coding.driver' => 'judge0', 'coding.judge0.url' => 'https://judge.test']);
    app()->forgetInstance(CodeRunner::class);
    [, $student, $course] = codingSetup();
    $lesson = ioExercise($course);

    Http::fake([
        'judge.test/submissions/batch?base64_encoded=true' => Http::response([['token' => 'a'], ['token' => 'b']]),
        'judge.test/submissions/batch?*' => Http::response(['submissions' => [
            ['status' => ['id' => 3], 'stdout' => base64_encode("16\n")],
            ['status' => ['id' => 3], 'stdout' => base64_encode("2.25\n")],
        ]]),
    ]);

    expect(app(CodeRunner::class))->toBeInstanceOf(Judge0Runner::class);
    $this->actingAs($student)->post("/lessons/{$lesson->id}/code/submit", ['code' => "s=float(input())\nprint(s*s)", 'outputs' => []]);

    expect(CodeSubmission::first())->status->toBe('passed')->graded_by->toBe('judge0');
    Http::assertSent(fn ($request) => $request->method() === 'POST' && base64_decode($request['submissions'][1]['stdin']) === '1.5' && $request['submissions'][0]['enable_network'] === false);
});

test('function harness extracts the value after the marker', function () {
    expect(Harness::extract("hello\n__RESULT__[1, 2]"))->toBe('[1, 2]')
        ->and(Harness::extract('no marker'))->toBeNull()
        ->and(Harness::python('def f(x): return x', 'f', '[1]'))->toContain("f(*__json.loads('[1]'))");
});

test('mission gain compares the first attempts of pre and post tests', function () {
    expect(StudentProgress::gain(40, 70))->toBe(0.5)
        ->and(StudentProgress::gain(100, 100))->toBeNull()
        ->and(StudentProgress::gain(null, 70))->toBeNull();

    [, $student, $course] = codingSetup();
    $make = function (string $role) use ($course) {
        $lesson = $course->lessons()->create(['title' => $role, 'type' => 'quiz', 'assessment' => $role, 'position' => 1]);
        $lesson->questions()->create(['prompt' => 'a', 'type' => 'single', 'concept' => 'loops', 'options' => [['text' => 'y', 'correct' => true], ['text' => 'n', 'correct' => false]]]);
        $lesson->questions()->create(['prompt' => 'b', 'type' => 'single', 'concept' => 'variables', 'options' => [['text' => 'y', 'correct' => true], ['text' => 'n', 'correct' => false]]]);

        return $lesson->load('questions');
    };
    $pre = $make('pre');
    $post = $make('post');
    $answer = fn ($lesson, $a, $b) => ['answers' => [$lesson->questions[0]->id => $a, $lesson->questions[1]->id => $b]];

    $this->actingAs($student)->post("/lessons/{$pre->id}/quiz", $answer($pre, 1, 0));
    $this->actingAs($student)->post("/lessons/{$post->id}/quiz", $answer($post, 0, 0));
    $this->actingAs($student)->post("/lessons/{$post->id}/quiz", $answer($post, 1, 1)); // later attempts do not count

    $missions = app(StudentProgress::class)->forStudent($student)->first()['missions'];
    expect($missions)->toMatchArray(['pre' => 50, 'post' => 100, 'gain' => 1.0])
        ->and($missions['concepts'])->toBe([['concept' => 'loops', 'pre' => 0, 'post' => 100], ['concept' => 'variables', 'pre' => 100, 'post' => 100]]);
});
