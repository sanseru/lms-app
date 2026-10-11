<?php

use Illuminate\Support\Facades\Cache;
use Modules\Learning\Models\QuizAttempt;
use Modules\Learning\Models\QuizQuestion;

function mixedQuiz($course, array $attributes = [])
{
    $quiz = $course->lessons()->create($attributes + ['title' => 'Mixed quiz', 'type' => 'quiz', 'position' => 1, 'pass_score' => 70, 'is_published' => true]);
    $quiz->questions()->create(['position' => 1, 'prompt' => 'Is Python case-sensitive?', 'type' => 'true_false', 'points' => 1, 'options' => [['text' => 'True', 'correct' => true], ['text' => 'False', 'correct' => false]]]);
    $quiz->questions()->create(['position' => 2, 'prompt' => 'Command for input?', 'type' => 'short', 'points' => 1, 'options' => [['text' => 'input', 'correct' => true], ['text' => 'input()', 'correct' => true]]]);
    $quiz->questions()->create(['position' => 3, 'prompt' => 'Half of 5?', 'type' => 'short', 'points' => 1, 'options' => [['text' => '2.5', 'correct' => true]]]);

    return $quiz->fresh('questions');
}

test('short answers ignore case, spaces, a final full stop, and compare numbers by value', function () {
    expect(QuizQuestion::sameText('  Input. ', 'input'))->toBeTrue()
        ->and(QuizQuestion::sameText('hello   world', 'Hello World'))->toBeTrue()
        ->and(QuizQuestion::sameText('2,50', '2.5'))->toBeTrue()
        ->and(QuizQuestion::sameText('25', '2.5'))->toBeFalse()
        ->and(QuizQuestion::sameText('inputs', 'input'))->toBeFalse();
});

test('true/false and short answer questions are graded and keys stay hidden', function () {
    [, $student, $course] = learningSetup();
    $quiz = mixedQuiz($course);
    [$tf, $short, $number] = $quiz->questions->pluck('id')->all();

    $this->actingAs($student)->get("/lessons/{$quiz->id}")
        ->assertInertia(fn ($page) => $page->where('questions.1.type', 'short')->where('questions.1.options', [])->where('questions.0.options', ['True', 'False'])->where('timer', null));

    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", ['answers' => [$tf => 0, $short => ' INPUT() ', $number => '2,5']])
        ->assertSessionHas('success');

    $attempt = QuizAttempt::first();
    expect($attempt->score)->toBe(100)
        ->and($attempt->answers[$short]['given'])->toBe('INPUT()')
        ->and($attempt->answers[$tf]['given'])->toBe([0]);

    $this->actingAs($student)->get("/lessons/{$quiz->id}")
        ->assertInertia(fn ($page) => $page->where("result.items.{$short}.given", 'INPUT()')->where("result.items.{$short}.answer", ['input', 'input()']));
});

test('a wrong short answer does not reveal accepted answers before passing', function () {
    [, $student, $course] = learningSetup();
    $quiz = mixedQuiz($course);
    [$tf, $short, $number] = $quiz->questions->pluck('id')->all();

    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", ['answers' => [$tf => 1, $short => 'print', $number => '']]);

    expect(QuizAttempt::first()->score)->toBe(0);
    $this->actingAs($student)->get("/lessons/{$quiz->id}")
        ->assertInertia(fn ($page) => $page->where("result.items.{$short}.answer", null)->where("result.items.{$short}.correct", false));
});

test('teachers save true/false and short answer questions with a time limit', function () {
    [$teacher, , $course] = learningSetup();

    $this->actingAs($teacher)->post("/courses/{$course->id}/lessons", [
        'title' => 'Quick quiz', 'type' => 'quiz', 'time_limit_minutes' => 5, 'is_published' => true,
        'questions' => [
            ['prompt' => 'Is the Earth round?', 'type' => 'true_false', 'points' => 1, 'options' => [['text' => 'True', 'correct' => true], ['text' => 'False', 'correct' => false]]],
            ['prompt' => 'Capital of Indonesia?', 'type' => 'short', 'points' => 2, 'options' => [['text' => ' Jakarta ', 'correct' => false]]],
        ],
    ])->assertSessionHasNoErrors();

    $lesson = $course->lessons()->first();
    expect($lesson->time_limit_minutes)->toBe(5)
        ->and($lesson->questions[1]->options)->toBe([['text' => 'Jakarta', 'correct' => true]]);

    $this->actingAs($teacher)->post("/courses/{$course->id}/lessons", [
        'title' => 'Invalid', 'type' => 'quiz',
        'questions' => [
            ['prompt' => 'A?', 'type' => 'true_false', 'points' => 1, 'options' => [['text' => 'True', 'correct' => true], ['text' => 'False', 'correct' => true]]],
            ['prompt' => 'B?', 'type' => 'true_false', 'points' => 1, 'options' => [['text' => 'True', 'correct' => true]]],
            ['prompt' => 'C?', 'type' => 'single', 'points' => 1, 'options' => [['text' => 'x', 'correct' => true]]],
            ['prompt' => 'D?', 'type' => 'short', 'points' => 1, 'options' => []],
        ],
    ])->assertSessionHasErrors(['questions.0.options', 'questions.1.options', 'questions.2.options', 'questions.3.options']);
});

test('timed quiz must be started and records how long it took', function () {
    [, $student, $course] = learningSetup();
    $quiz = mixedQuiz($course, ['time_limit_minutes' => 5]);
    [$tf, $short, $number] = $quiz->questions->pluck('id')->all();
    $answers = ['answers' => [$tf => 0, $short => 'input', $number => '2.5']];

    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", $answers)->assertSessionHas('error');
    expect(QuizAttempt::count())->toBe(0);

    $this->travelTo(now()->startOfMinute());
    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz/start")->assertRedirect();
    $this->travel(2)->minutes();
    // Starting again does not reset the clock.
    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz/start");
    $this->actingAs($student)->get("/lessons/{$quiz->id}")->assertInertia(fn ($page) => $page->where('timer.limit', 5)->where('timer.remaining', 180));

    $this->travel(30)->seconds();
    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", $answers)->assertSessionHas('success');

    $attempt = QuizAttempt::first();
    expect($attempt->duration_seconds)->toBe(150)->and($attempt->late)->toBeFalse()->and($attempt->passed)->toBeTrue();
    $this->actingAs($student)->get("/lessons/{$quiz->id}")->assertInertia(fn ($page) => $page->where('timer.remaining', null)->where('result.duration', 150));
});

test('answers sent after the time limit are saved but do not pass', function () {
    [, $student, $course] = learningSetup();
    $quiz = mixedQuiz($course, ['time_limit_minutes' => 1]);
    [$tf, $short, $number] = $quiz->questions->pluck('id')->all();

    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz/start");
    $this->travel(2)->minutes();
    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", ['answers' => [$tf => 0, $short => 'input', $number => '2.5']])
        ->assertSessionHas('error');

    $attempt = QuizAttempt::first();
    expect($attempt->score)->toBe(100)->and($attempt->late)->toBeTrue()->and($attempt->passed)->toBeFalse();
});

test('a timed quiz cannot be started once attempts run out', function () {
    [, $student, $course] = learningSetup();
    $quiz = mixedQuiz($course, ['time_limit_minutes' => 5, 'max_attempts' => 1]);
    $quiz->attempts()->create(['student_id' => $student->id, 'answers' => [], 'score' => 0, 'earned_points' => 0, 'total_points' => 3, 'passed' => false]);

    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz/start")->assertSessionHas('error');
    expect(Cache::has("quiz-timer:{$quiz->id}:{$student->id}"))->toBeFalse();
    $this->actingAs(userWithRole('student'))->post("/lessons/{$quiz->id}/quiz/start")->assertForbidden();
});
