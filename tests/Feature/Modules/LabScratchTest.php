<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Lab\Models\LabProject;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Lesson;

/** A small real .sb3: a zip archive with a project.json, like the editor sends. */
function sb3(string $name = 'project.sb3'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'sb3');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('project.json', json_encode(['targets' => [], 'meta' => ['semver' => '3.0.0']]));
    $zip->close();

    // A real upload (not a fake), so the type is read from the content as in production.
    return new UploadedFile($path, $name, null, null, true);
}

function scratchProject($owner, array $attrs = []): LabProject
{
    $project = new LabProject(['user_id' => $owner->id, 'title' => 'Game', 'language' => 'scratch']);
    $project->forceFill($attrs)->save();

    return $project;
}

beforeEach(fn () => Storage::fake('local'));

test('a scratch project starts empty and saves the .sb3 from the editor', function () {
    $student = userWithRole('student');

    $this->actingAs($student)->post('/lab', ['title' => 'Cat chase', 'language' => 'scratch'])->assertRedirect();
    $project = LabProject::sole();
    expect($project->code)->toBeNull()->and($project->file_path)->toBeNull();

    $this->actingAs($student)->get("/lab/{$project->id}")
        ->assertInertia(fn ($p) => $p->component('Lab::Lab/Editor')->where('project.language', 'scratch')->where('project.file_url', null));

    $this->actingAs($student)->post("/lab/{$project->id}", ['_method' => 'put', 'title' => 'Cat chase', 'file' => sb3()])->assertSessionHasNoErrors();
    $project->refresh();
    Storage::disk('local')->assertExists($project->file_path);

    // Saving again replaces the file instead of piling them up.
    $first = $project->file_path;
    $this->actingAs($student)->post("/lab/{$project->id}", ['_method' => 'put', 'title' => 'Cat chase 2', 'file' => sb3()])->assertSessionHasNoErrors();
    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($project->fresh()->file_path);

    $this->actingAs($student)->get("/lab/{$project->id}")->assertInertia(fn ($p) => $p->where('project.file_url', fn ($url) => str_contains($url, "/community/{$project->id}/file")));
    $this->actingAs($student)->get("/community/{$project->id}/file")->assertOk()->assertHeader('Content-Type', 'application/x.scratch.sb3');

    $path = $project->fresh()->file_path;
    $this->actingAs($student)->delete("/lab/{$project->id}");
    Storage::disk('local')->assertMissing($path);
});

test('only an .sb3 is accepted, and only for scratch projects', function () {
    $student = userWithRole('student');
    $scratch = scratchProject($student);
    $python = LabProject::create(['user_id' => $student->id, 'title' => 'Py', 'language' => 'python', 'code' => 'print(1)']);

    $this->actingAs($student)->post("/lab/{$scratch->id}", ['_method' => 'put', 'title' => 'X', 'file' => UploadedFile::fake()->createWithContent('evil.sb3', '<?php echo 1;')])
        ->assertSessionHasErrors(['file' => 'That is not a Scratch project (.sb3).']);
    $this->actingAs($student)->post("/lab/{$python->id}", ['_method' => 'put', 'title' => 'X', 'code' => 'print(1)', 'file' => sb3()])
        ->assertSessionHasErrors('file');

    expect($scratch->fresh()->file_path)->toBeNull();
});

test('a scratch file is private until shared, then everyone signed in can play it', function () {
    $owner = userWithRole('student');
    $project = scratchProject($owner);
    $this->actingAs($owner)->post("/lab/{$project->id}", ['_method' => 'put', 'title' => 'Game', 'file' => sb3()]);

    $classmate = userWithRole('student');
    $this->actingAs($classmate)->get("/community/{$project->id}/file")->assertForbidden();

    $project->forceFill(['shared_at' => now()])->save();
    foreach ([$classmate, userWithRole('guardian'), userWithRole('teacher')] as $viewer) {
        $this->actingAs($viewer)->get("/community/{$project->id}/file")->assertOk();
    }
    $this->actingAs($classmate)->get("/community/{$project->id}")
        ->assertInertia(fn ($p) => $p->where('project.language', 'scratch')->where('project.file_url', fn ($url) => $url !== null));

    // A python project has no file to serve.
    $python = scratchProject($owner, ['language' => 'python', 'code' => 'print(1)', 'shared_at' => now()]);
    $this->actingAs($classmate)->get("/community/{$python->id}/file")->assertNotFound();
});

test('a presentation starts with sample slides and saves like code', function () {
    $student = userWithRole('student');

    $this->actingAs($student)->post('/lab', ['title' => 'Volcanoes', 'language' => 'presentation'])->assertRedirect();
    $project = LabProject::sole();
    expect($project->code)->toBe(LabProject::STARTERS['presentation']);

    $this->actingAs($student)->put("/lab/{$project->id}", ['title' => 'Volcanoes', 'code' => "# Hot\n---\n# Lava"])->assertSessionHasNoErrors();
    expect($project->fresh()->code)->toBe("# Hot\n---\n# Lava");

    $this->actingAs($student)->get('/lab')->assertInertia(fn ($p) => $p->where('types', fn ($types) => collect($types)->pluck('key')->all() === ['python', 'scratch', 'presentation', 'html', 'javascript']));
    $this->actingAs($student)->get('/community?language=presentation')->assertInertia(fn ($p) => $p->where('filters.language', 'presentation'));
});

test('a teacher makes an assignment that students build in the scratch editor', function () {
    [$teacher, $student, $course] = learningSetup();

    $this->actingAs($teacher)->post("/courses/{$course->id}/lessons", ['title' => 'Chase game', 'type' => 'assignment', 'max_score' => 100, 'workspace' => 'scratch', 'is_published' => true])->assertSessionHasNoErrors();
    $lesson = Lesson::where('title', 'Chase game')->sole();
    expect($lesson->workspace)->toBe('scratch');

    $this->actingAs($student)->get("/lessons/{$lesson->id}")->assertInertia(fn ($p) => $p->where('lesson.workspace', 'scratch'));

    $this->actingAs($student)->post("/lessons/{$lesson->id}/submission", ['body' => '', 'file' => sb3('scratch-project.sb3')])->assertSessionHasNoErrors();
    $submission = AssignmentSubmission::sole();
    expect($submission->file_name)->toBe('scratch-project.sb3')->and($submission->toItem()['is_scratch'])->toBeTrue();

    // The workspace only belongs to assignments.
    $this->actingAs($teacher)->post("/courses/{$course->id}/lessons", ['title' => 'Read me', 'type' => 'material', 'workspace' => 'scratch', 'is_published' => true]);
    expect(Lesson::where('title', 'Read me')->sole()->workspace)->toBeNull();

    $this->actingAs($teacher)->post("/courses/{$course->id}/lessons", ['title' => 'Bad', 'type' => 'assignment', 'workspace' => 'minecraft'])->assertSessionHasErrors('workspace');
});
