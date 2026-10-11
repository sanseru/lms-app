<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Classes\Models\ClassRoom;
use Modules\Forum\Models\Answer;
use Modules\Forum\Models\Question;
use Modules\Learning\Models\Course;

/** A class with a teacher, two students and a course with one lesson. */
function forumSetup(): array
{
    $teacher = userWithRole('teacher');
    [$a, $b] = [userWithRole('student'), userWithRole('student')];
    $course = Course::create(['title' => 'Python', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $lesson = $course->lessons()->create(['title' => 'Perulangan', 'type' => 'material', 'position' => 1, 'is_published' => true]);
    $class = ClassRoom::create([
        'code' => 'PY-'.fake()->unique()->numberBetween(100, 999), 'name' => 'Python 1', 'subject' => 'Python', 'level' => 'beginner',
        'teacher_id' => $teacher->id, 'schedule_days' => [2], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'violet',
    ]);
    $class->forceFill(['course_id' => $course->id])->saveQuietly();
    $class->students()->attach([$a->id, $b->id]);

    return compact('teacher', 'a', 'b', 'class', 'lesson');
}

function ask(array $s, array $data = []): Question
{
    test()->actingAs($s['a'])->post('/questions', $data + [
        'class_room_id' => $s['class']->id, 'title' => 'Kenapa error indent?', 'body' => 'Tolong bantu', 'visibility' => 'class',
    ])->assertSessionHasNoErrors();

    return Question::latest('id')->first();
}

test('student asks a question about a lesson in their class', function () {
    $s = forumSetup();
    $q = ask($s, ['lesson_id' => $s['lesson']->id, 'code' => "for i in range(3):\nprint(i)"]);

    expect($q->author_id)->toBe($s['a']->id)
        ->and($q->lesson_id)->toBe($s['lesson']->id)
        ->and($q->status())->toBe('unanswered');

    $this->actingAs($s['b'])->get("/questions/{$q->id}")->assertOk()
        ->assertInertia(fn ($p) => $p->component('Forum::Questions/Show')->where('question.code', "for i in range(3):\nprint(i)")->where('can.answer', true));
});

test('student cannot ask in a class they are not enrolled in or about another course lesson', function () {
    $s = forumSetup();
    $other = forumSetup();

    $this->actingAs($s['a'])->post('/questions', ['class_room_id' => $other['class']->id, 'title' => 'Halo semua', 'body' => 'x', 'visibility' => 'class'])->assertForbidden();
    $this->actingAs($s['a'])->post('/questions', [
        'class_room_id' => $s['class']->id, 'lesson_id' => $other['lesson']->id, 'title' => 'Halo semua', 'body' => 'x', 'visibility' => 'class',
    ])->assertSessionHasErrors('lesson_id');
});

test('private questions are hidden from classmates but visible to teacher and parent', function () {
    $s = forumSetup();
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($s['a']->id, ['relationship' => 'ibu']);
    $q = ask($s, ['visibility' => 'private']);

    $this->actingAs($s['b'])->get("/questions/{$q->id}")->assertForbidden();
    $this->actingAs($s['teacher'])->get("/questions/{$q->id}")->assertOk();
    $this->actingAs($guardian)->get("/questions/{$q->id}")->assertOk()->assertInertia(fn ($p) => $p->where('can.answer', false));
    $this->actingAs($guardian)->post("/questions/{$q->id}/answers", ['body' => 'Coba lagi nak'])->assertForbidden();
    $this->actingAs(userWithRole('teacher'))->get("/questions/{$q->id}")->assertForbidden();

    $this->actingAs($s['b'])->get('/questions')->assertInertia(fn ($p) => $p->where('counts.all', 0));
});

test('anonymous questions hide the name from classmates only', function () {
    $s = forumSetup();
    $q = ask($s, ['anonymous' => true]);

    $this->actingAs($s['b'])->get("/questions/{$q->id}")->assertInertia(fn ($p) => $p->where('question.author.name', 'A classmate'));
    $this->actingAs($s['teacher'])->get("/questions/{$q->id}")->assertInertia(fn ($p) => $p->where('question.author.name', $s['a']->name));
});

test('teacher answer marks question as answered by staff and is listed first', function () {
    $s = forumSetup();
    $q = ask($s);

    $this->actingAs($s['b'])->post("/questions/{$q->id}/answers", ['body' => 'Coba tambah spasi'])->assertSessionHasNoErrors();
    expect($q->fresh()->staff_answered_at)->toBeNull();
    $this->actingAs($s['teacher'])->get('/questions?tab=unanswered')->assertInertia(fn ($p) => $p->where('counts.unanswered', 1));

    $this->actingAs($s['teacher'])->post("/questions/{$q->id}/answers", ['body' => 'Indentasi 4 spasi ya'])->assertSessionHasNoErrors();
    $q->refresh();
    expect($q->staff_answered_at)->not->toBeNull()->and($q->answers_count)->toBe(2);

    $this->actingAs($s['a'])->get("/questions/{$q->id}")
        ->assertInertia(fn ($p) => $p->where('answers.0.by_staff', true)->where('answers.1.by_staff', false));
    $this->actingAs($s['teacher'])->get('/questions')->assertInertia(fn ($p) => $p->where('counts.unanswered', 0));
});

test('asker accepts an answer which resolves the question; classmates cannot', function () {
    $s = forumSetup();
    $q = ask($s);
    $this->actingAs($s['b'])->post("/questions/{$q->id}/answers", ['body' => 'Pakai int()']);
    $answer = Answer::first();

    $this->actingAs($s['b'])->post("/questions/answers/{$answer->id}/accept")->assertForbidden();
    $this->actingAs($s['a'])->post("/questions/answers/{$answer->id}/accept")->assertRedirect();

    $q->refresh();
    expect($q->accepted_answer_id)->toBe($answer->id)->and($q->status())->toBe('resolved');

    // Deleting the best answer reopens the question.
    $this->actingAs($s['teacher'])->delete("/questions/answers/{$answer->id}")->assertRedirect();
    expect($q->fresh()->status())->toBe('unanswered')->and($q->fresh()->accepted_answer_id)->toBeNull();
});

test('votes toggle and cannot be cast on own content', function () {
    $s = forumSetup();
    $q = ask($s);

    $this->actingAs($s['a'])->post("/questions/{$q->id}/upvote")->assertForbidden();
    $this->actingAs($s['b'])->post("/questions/{$q->id}/upvote");
    expect($q->fresh()->votes_count)->toBe(1);
    $this->actingAs($s['b'])->post("/questions/{$q->id}/upvote");
    expect($q->fresh()->votes_count)->toBe(0);
});

test('only teacher can pin; author can delete only before answers', function () {
    $s = forumSetup();
    $q = ask($s);

    $this->actingAs($s['a'])->post("/questions/{$q->id}/pin")->assertForbidden();
    $this->actingAs($s['teacher'])->post("/questions/{$q->id}/pin");
    expect($q->fresh()->pinned)->toBeTrue();

    $this->actingAs($s['b'])->post("/questions/{$q->id}/answers", ['body' => 'Jawabanku']);
    $this->actingAs($s['a'])->delete("/questions/{$q->id}")->assertForbidden();
    $this->actingAs($s['teacher'])->delete("/questions/{$q->id}")->assertRedirect('/questions');
    expect(Question::count())->toBe(0)->and(Answer::count())->toBe(0);
});

test('question image is stored privately and served only to viewers', function () {
    Storage::fake('local');
    $s = forumSetup();
    $q = ask($s, ['image' => UploadedFile::fake()->image('error.png'), 'visibility' => 'private']);

    Storage::disk('local')->assertExists($q->image_path);
    $this->actingAs($s['teacher'])->get("/questions/{$q->id}/image")->assertOk();
    $this->actingAs($s['b'])->get("/questions/{$q->id}/image")->assertForbidden();
});

test('lesson summary powers the ask card on lesson pages', function () {
    $s = forumSetup();
    ask($s, ['lesson_id' => $s['lesson']->id]);

    $this->actingAs($s['b'])->getJson("/questions/lesson/{$s['lesson']->id}")
        ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('can_ask', true);
});

test('teacher dashboard shows unanswered questions', function () {
    $s = forumSetup();
    ask($s);

    $this->actingAs($s['teacher'])->get('/dashboard')->assertInertia(fn ($p) => $p->where('stats.questions_waiting', 1));
});
