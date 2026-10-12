<?php

use App\Models\User;
use Modules\Lab\Models\LabProject;

function labProject(User $owner, array $attrs = []): LabProject
{
    $project = new LabProject(['user_id' => $owner->id, 'title' => 'Times table', 'language' => 'python', 'code' => 'print(1)']);
    $project->forceFill($attrs)->save();

    return $project;
}

test('student creates a lab project with starter code and saves it', function () {
    $student = userWithRole('student', ['name' => 'Aldi Pratama']);

    $this->actingAs($student)->post('/lab', ['title' => 'Guessing game', 'language' => 'python'])->assertRedirect();
    $project = LabProject::sole();
    expect($project->user_id)->toBe($student->id)->and($project->code)->toBe(LabProject::STARTERS['python']);

    $this->actingAs($student)->get("/lab/{$project->id}")->assertOk()
        ->assertInertia(fn ($p) => $p->component('Lab::Lab/Editor')->where('project.title', 'Guessing game')->where('project.author.name', 'Aldi P.'));

    $this->actingAs($student)->put("/lab/{$project->id}", ['title' => 'Guess it', 'description' => 'Fun', 'code' => 'print(2)'])->assertSessionHasNoErrors();
    expect($project->fresh())->title->toBe('Guess it')->code->toBe('print(2)')->language->toBe('python');
});

test('lab validates the project type and keeps the type fixed after creation', function () {
    $student = userWithRole('student');

    $this->actingAs($student)->post('/lab', ['title' => 'X', 'language' => 'scratch'])->assertSessionHasErrors(['language' => 'The selected project type is invalid.']);

    $project = labProject($student);
    $this->actingAs($student)->put("/lab/{$project->id}", ['title' => 'X', 'code' => '', 'language' => 'html'])->assertSessionHasErrors('language');
});

test('only the owner can open, edit, share, or delete a lab project', function () {
    $owner = userWithRole('student');
    $other = userWithRole('student');
    $project = labProject($owner);

    $this->actingAs($other)->get("/lab/{$project->id}")->assertForbidden();
    $this->actingAs($other)->put("/lab/{$project->id}", ['title' => 'Hacked', 'code' => ''])->assertForbidden();
    $this->actingAs($other)->post("/lab/{$project->id}/share")->assertForbidden();
    $this->actingAs($other)->delete("/lab/{$project->id}")->assertForbidden();
    $this->actingAs($other)->get("/community/{$project->id}")->assertForbidden();

    $this->actingAs($other)->get('/lab')->assertInertia(fn ($p) => $p->has('projects', 0));
});

test('admins and teachers cannot edit or delete a student lab project', function () {
    $owner = userWithRole('student');
    $project = labProject($owner, ['shared_at' => now()]);

    foreach ([userWithRole('admin'), userWithRole('teacher')] as $staff) {
        $this->actingAs($staff)->get("/lab/{$project->id}")->assertForbidden();
        $this->actingAs($staff)->put("/lab/{$project->id}", ['title' => 'Changed', 'code' => ''])->assertForbidden();
        $this->actingAs($staff)->delete("/lab/{$project->id}/share")->assertForbidden();
        $this->actingAs($staff)->delete("/lab/{$project->id}")->assertForbidden();
        $this->actingAs($staff)->get("/community/{$project->id}")->assertInertia(fn ($p) => $p->where('can.edit', false));
    }

    expect($project->fresh())->title->toBe('Times table')->shared_at->not->toBeNull();
});

test('parents cannot use the laboratory but can browse the community', function () {
    $guardian = userWithRole('guardian');
    $project = labProject(userWithRole('student'), ['shared_at' => now()]);

    $this->actingAs($guardian)->get('/lab')->assertForbidden();
    $this->actingAs($guardian)->get('/community')->assertOk()->assertInertia(fn ($p) => $p->has('projects.data', 1)->where('can.create', false));
    $this->actingAs($guardian)->get("/community/{$project->id}")->assertOk()->assertInertia(fn ($p) => $p->where('can.interact', false));
    $this->actingAs($guardian)->post("/community/{$project->id}/like")->assertForbidden();
});

test('shared projects show in the community with likes, unique views, and comments', function () {
    $owner = userWithRole('student');
    [$a, $b] = [userWithRole('student'), userWithRole('student')];
    $project = labProject($owner);
    labProject($owner, ['title' => 'Private one']);

    $this->actingAs($owner)->post("/lab/{$project->id}/share")->assertSessionHas('success');
    expect($project->fresh()->isShared())->toBeTrue();

    $this->actingAs($a)->post("/community/{$project->id}/like");
    $this->actingAs($b)->post("/community/{$project->id}/like");
    $this->actingAs($b)->post("/community/{$project->id}/like"); // toggles off
    $this->actingAs($a)->get("/community/{$project->id}")->assertOk();
    $this->actingAs($a)->get("/community/{$project->id}");
    $this->actingAs($owner)->get("/community/{$project->id}"); // the owner's own visits don't count
    $this->actingAs($a)->post("/community/{$project->id}/comments", ['body' => 'Cool!'])->assertSessionHasNoErrors();

    $this->actingAs($b)->get('/community?sort=popular')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Lab::Community/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.title', 'Times table')
            ->where('projects.data.0.likes_count', 1)
            ->where('projects.data.0.views_count', 1)
            ->where('projects.data.0.comments_count', 1)
            ->where('projects.data.0.liked', false)
            ->has('trending', 1));

    $this->actingAs($a)->get('/community?q=nothing-here')->assertInertia(fn ($p) => $p->has('projects.data', 0));
    $this->actingAs($a)->get('/community?language=html')->assertInertia(fn ($p) => $p->has('projects.data', 0));
});

test('comments can be deleted by the writer, the project owner, or a teacher only', function () {
    $owner = userWithRole('student');
    [$writer, $other] = [userWithRole('student'), userWithRole('student')];
    $project = labProject($owner, ['shared_at' => now()]);
    $comment = fn () => $project->comments()->create(['user_id' => $writer->id, 'body' => 'Hi']);

    $c = $comment();
    $this->actingAs($other)->delete("/community/comments/{$c->id}")->assertForbidden();
    $this->actingAs($writer)->delete("/community/comments/{$c->id}")->assertRedirect();
    $this->actingAs($owner)->delete('/community/comments/'.$comment()->id)->assertRedirect();
    $this->actingAs(userWithRole('teacher'))->delete('/community/comments/'.$comment()->id)->assertRedirect();
    expect($project->comments()->count())->toBe(0);
});

test('a teacher removes a project from the community and the owner cannot re-share it', function () {
    $owner = userWithRole('student');
    $teacher = userWithRole('teacher');
    $project = labProject($owner, ['shared_at' => now()]);

    $this->actingAs(userWithRole('student'))->post("/community/{$project->id}/hide")->assertForbidden();
    $this->actingAs($teacher)->post("/community/{$project->id}/hide")->assertRedirect('/community');
    expect($project->fresh()->isShared())->toBeFalse();

    $this->actingAs(userWithRole('student'))->get("/community/{$project->id}")->assertForbidden();
    $this->actingAs(userWithRole('student'))->get('/community')->assertInertia(fn ($p) => $p->has('projects.data', 0));
    $this->actingAs($owner)->post("/lab/{$project->id}/share")->assertSessionHas('error');
    expect($project->fresh()->isShared())->toBeFalse();

    $this->actingAs($teacher)->delete("/community/{$project->id}/hide");
    expect($project->fresh()->isShared())->toBeTrue();
});

test('public names hide the family name', function () {
    expect(LabProject::publicName(new User(['name' => 'Nadia Putri Lestari'])))->toBe('Nadia L.')
        ->and(LabProject::publicName(new User(['name' => 'Raka'])))->toBe('Raka');
});
