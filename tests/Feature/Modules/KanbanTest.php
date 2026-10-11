<?php

use Modules\Classes\Models\ClassRoom;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Card;
use Modules\Kanban\Models\Project;

function kanbanSetup(array $project = []): array
{
    $teacher = userWithRole('teacher');
    [$a, $b] = [userWithRole('student'), userWithRole('student')];
    $class = ClassRoom::create([
        'code' => 'KB-'.fake()->unique()->numberBetween(100, 999), 'name' => 'Scratch 1', 'subject' => 'Scratch', 'level' => 'beginner',
        'teacher_id' => $teacher->id, 'schedule_days' => [3], 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'active', 'color' => 'amber',
    ]);
    $class->students()->attach([$a->id, $b->id]);

    test()->actingAs($teacher)->post('/projects', $project + [
        'class_room_id' => $class->id, 'title' => 'Game Tangkap Bola', 'emoji' => '🎮', 'status' => 'active', 'requires_review' => true,
        'tasks' => [['title' => 'Tulis ide game'], ['title' => 'Buat sprite'], ['title' => 'Tambah skor']],
    ])->assertSessionHasNoErrors();

    return [$teacher, $a, $b, Project::latest('id')->first(), $class];
}

function boardOf(Project $project, $student): Board
{
    return $project->boards()->where('student_id', $student->id)->first();
}

test('creating a project gives every student a board with the teacher steps', function () {
    [, $a, $b, $project] = kanbanSetup();

    expect($project->boards)->toHaveCount(2)
        ->and(boardOf($project, $a)->cards()->pluck('title')->all())->toBe(['Tulis ide game', 'Buat sprite', 'Tambah skor'])
        ->and(boardOf($project, $b)->cards()->where('column', 'todo')->count())->toBe(3);
});

test('teacher can only create projects for their own classes', function () {
    [, , , , $class] = kanbanSetup();

    $this->actingAs(userWithRole('teacher'))->post('/projects', [
        'class_room_id' => $class->id, 'title' => 'X', 'emoji' => '🚀', 'status' => 'active',
    ])->assertSessionHasErrors('class_room_id');
    $this->actingAs(userWithRole('student'))->post('/projects', ['class_room_id' => $class->id, 'title' => 'X', 'emoji' => '🚀', 'status' => 'active'])->assertForbidden();
});

test('student moves cards but needs the teacher to approve them into done', function () {
    [$teacher, $a, , $project] = kanbanSetup();
    $card = boardOf($project, $a)->cards()->first();

    $this->actingAs($a)->post("/projects/cards/{$card->id}/move", ['column' => 'doing', 'index' => 0])->assertRedirect();
    expect($card->fresh()->column)->toBe('doing');

    $this->actingAs($a)->post("/projects/cards/{$card->id}/move", ['column' => 'done', 'index' => 0])->assertForbidden();
    $this->actingAs($a)->post("/projects/cards/{$card->id}/move", ['column' => 'review', 'index' => 0]);

    $this->actingAs($teacher)->post("/projects/cards/{$card->id}/review", ['decision' => 'return'])->assertSessionHasErrors('feedback');
    $this->actingAs($teacher)->post("/projects/cards/{$card->id}/review", ['decision' => 'return', 'feedback' => 'Tambahkan aturan main'])->assertRedirect();
    expect($card->fresh())->column->toBe('doing')->feedback->toBe('Tambahkan aturan main');

    $this->actingAs($a)->post("/projects/cards/{$card->id}/move", ['column' => 'review', 'index' => 0]);
    $this->actingAs($teacher)->post("/projects/cards/{$card->id}/review", ['decision' => 'approve']);
    expect($card->fresh())->column->toBe('done')->reviewed_by->toBe($teacher->id)->done_at->not->toBeNull();

    $this->actingAs($a)->post("/projects/cards/{$card->id}/move", ['column' => 'todo', 'index' => 0])->assertForbidden();
});

test('without review students can finish cards and the board completes', function () {
    [, $a, , $project] = kanbanSetup(['requires_review' => false]);
    $board = boardOf($project, $a);

    foreach ($board->cards as $card) {
        $this->actingAs($a)->post("/projects/cards/{$card->id}/move", ['column' => 'done', 'index' => 9])->assertRedirect();
    }

    expect($board->fresh()->completed_at)->not->toBeNull()
        ->and($board->fresh()->progress()['percent'])->toBe(100);
});

test('moving keeps positions tidy', function () {
    [, $a, , $project] = kanbanSetup();
    $board = boardOf($project, $a);
    [$c1, $c2, $c3] = $board->cards->all();

    $this->actingAs($a)->post("/projects/cards/{$c3->id}/move", ['column' => 'todo', 'index' => 0]);

    expect($board->cards()->where('column', 'todo')->orderBy('position')->pluck('id')->all())->toBe([$c3->id, $c1->id, $c2->id]);
});

test('students cannot touch other boards and can only edit their own cards', function () {
    [, $a, $b, $project] = kanbanSetup();
    $boardA = boardOf($project, $a);
    $teacherCard = $boardA->cards()->first();

    $this->actingAs($b)->get("/projects/boards/{$boardA->id}")->assertForbidden();
    $this->actingAs($b)->post("/projects/cards/{$teacherCard->id}/move", ['column' => 'doing', 'index' => 0])->assertForbidden();

    $this->actingAs($a)->put("/projects/cards/{$teacherCard->id}", ['title' => 'Ganti'])->assertForbidden();
    $this->actingAs($a)->delete("/projects/cards/{$teacherCard->id}")->assertForbidden();

    $this->actingAs($a)->post("/projects/boards/{$boardA->id}/cards", ['title' => 'Cari musik latar'])->assertSessionHasNoErrors();
    $own = Card::firstWhere('title', 'Cari musik latar');
    $this->actingAs($a)->put("/projects/cards/{$own->id}", ['title' => 'Cari musik latar yang seru'])->assertRedirect();
    $this->actingAs($a)->delete("/projects/cards/{$own->id}")->assertRedirect();
    expect(Card::find($own->id))->toBeNull();
});

test('parents can view their child board read-only', function () {
    [, $a, , $project] = kanbanSetup();
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($a->id, ['relationship' => 'ayah']);
    $board = boardOf($project, $a);

    $this->actingAs($guardian)->get('/projects')->assertInertia(fn ($p) => $p->where('mode', 'guardian')->has('projects', 1));
    $this->actingAs($guardian)->get("/projects/boards/{$board->id}")->assertOk()->assertInertia(fn ($p) => $p->where('can.work', false));
    $this->actingAs($guardian)->post("/projects/cards/{$board->cards()->first()->id}/move", ['column' => 'doing', 'index' => 0])->assertForbidden();
});

test('editing template steps updates student boards and late joiners get a board', function () {
    [$teacher, $a, , $project, $class] = kanbanSetup();
    $tasks = $project->templateTasks;

    $this->actingAs($teacher)->put("/projects/{$project->id}", [
        'title' => $project->title, 'emoji' => '🎮', 'status' => 'active', 'requires_review' => true,
        'tasks' => [['id' => $tasks[0]->id, 'title' => 'Tulis ide dan aturan game'], ['id' => $tasks[1]->id, 'title' => 'Buat sprite'], ['title' => 'Uji coba']],
    ])->assertSessionHasNoErrors();

    expect(boardOf($project, $a)->cards()->pluck('title')->all())->toBe(['Tulis ide dan aturan game', 'Buat sprite', 'Uji coba']);

    $late = userWithRole('student');
    $class->students()->attach($late->id);
    $this->actingAs($late)->get("/projects/{$project->id}")->assertRedirect();
    expect(boardOf($project, $late)->cards)->toHaveCount(3);
});

test('archived projects lock student boards', function () {
    [$teacher, $a, , $project] = kanbanSetup();
    $project->update(['status' => 'archived']);
    $card = boardOf($project, $a)->cards()->first();

    $this->actingAs($a)->post("/projects/cards/{$card->id}/move", ['column' => 'doing', 'index' => 0])->assertForbidden();
    $this->actingAs($teacher)->post("/projects/cards/{$card->id}/move", ['column' => 'doing', 'index' => 0])->assertRedirect();
});

test('dashboards show project progress and cards waiting for review', function () {
    [$teacher, $a, , $project] = kanbanSetup();
    $card = boardOf($project, $a)->cards()->first();
    $this->actingAs($a)->post("/projects/cards/{$card->id}/move", ['column' => 'review', 'index' => 0]);

    $this->actingAs($teacher)->get('/dashboard')->assertInertia(fn ($p) => $p->where('stats.cards_to_review', 1));
    $this->actingAs($a)->get('/dashboard')->assertInertia(fn ($p) => $p->has('projects', 1)->where('projects.0.board.progress.counts.review', 1));
});

test('project lists load with several projects without lazy loading', function () {
    [$teacher, $a, , $project, $class] = kanbanSetup();
    $this->actingAs($teacher)->post('/projects', [
        'class_room_id' => $class->id, 'title' => 'Proyek kedua', 'emoji' => '🤖', 'status' => 'active', 'tasks' => [['title' => 'Mulai']],
    ]);

    $this->actingAs($a)->get('/projects')->assertOk()->assertInertia(fn ($p) => $p->where('mode', 'student')->has('projects', 2));
    $this->actingAs($teacher)->get('/projects')->assertOk()->assertInertia(fn ($p) => $p->where('mode', 'staff')->has('projects', 2));
    $this->actingAs($a)->get('/dashboard')->assertOk()->assertInertia(fn ($p) => $p->has('projects', 2));
});

test('teacher overview lists the review queue across students', function () {
    [$teacher, $a, $b, $project] = kanbanSetup();
    foreach ([$a, $b] as $student) {
        foreach (boardOf($project, $student)->cards()->take(2)->get() as $card) {
            $this->actingAs($student)->post("/projects/cards/{$card->id}/move", ['column' => 'review', 'index' => 0]);
        }
    }
    $this->actingAs($teacher)->post('/projects/cards/'.boardOf($project, $a)->cards()->where('column', 'review')->first()->id.'/review', ['decision' => 'approve']);

    $this->actingAs($teacher)->get("/projects/{$project->id}")->assertOk()
        ->assertInertia(fn ($p) => $p->component('Kanban::Projects/Show')->has('reviews', 3)->has('boards', 2));
    $this->actingAs($teacher)->get('/projects/boards/'.boardOf($project, $a)->id)->assertOk()
        ->assertInertia(fn ($p) => $p->where('can.review', true)->has('cards', 3));
});
