<?php

namespace Modules\Coding\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Coding\Grading\Grader;
use Modules\Coding\Http\Requests\ExerciseRequest;
use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeSubmission;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;

class ExerciseController extends Controller
{
    public function create(Course $course): Response
    {
        return Inertia::render('Coding::Exercises/Form', $this->options() + ['course' => $course->only('id', 'title'), 'lesson' => null, 'exercise' => null, 'tests' => []]);
    }

    public function store(ExerciseRequest $request, Course $course): RedirectResponse
    {
        $lesson = DB::transaction(function () use ($request, $course) {
            $lesson = $course->lessons()->create($this->lessonData($request) + ['type' => 'code', 'position' => $course->nextPosition()]);
            $this->saveExercise($lesson, $request);

            return $lesson;
        });

        return $this->afterSave($lesson, $request);
    }

    public function edit(Request $request, Lesson $lesson): Response
    {
        $exercise = CodeExercise::with('tests')->where('lesson_id', $lesson->id)->firstOrFail();

        return Inertia::render('Coding::Exercises/Form', [
            ...$this->options(),
            'course' => $lesson->course->only('id', 'title'),
            'lesson' => $lesson->only('id', 'title', 'body', 'duration_minutes', 'is_published'),
            'exercise' => $exercise->only('id', 'language', 'mode', 'starter_code', 'solution_code', 'function_name', 'hints', 'time_limit_ms', 'show_solution_after', 'case_sensitive', 'concept', 'level', 'version')
                + ['verified' => $exercise->isVerified(), 'rules' => $exercise->rules ?? [], 'graded_on_version' => CodeSubmission::where('lesson_id', $lesson->id)->where('exercise_version', $exercise->version)->count()],
            'tests' => $exercise->tests->map->toEditor(),
            'autoVerify' => $request->boolean('verify'),
            'autoPublish' => $request->boolean('publish'),
        ]);
    }

    public function update(ExerciseRequest $request, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->type === 'code', 404);

        DB::transaction(function () use ($request, $lesson) {
            $lesson->update($this->lessonData($request));
            $this->saveExercise($lesson, $request);
        });

        return $this->afterSave($lesson, $request);
    }

    /**
     * "Uji solusi": solusi tutor harus lulus semua tes sebelum soal boleh terbit.
     * Pada driver browser, solusi dijalankan di browser tutor lalu outputnya dikirim ke sini.
     */
    public function verify(Request $request, Lesson $lesson, Grader $grader): RedirectResponse
    {
        Gate::authorize('learning.manage');
        $exercise = CodeExercise::where('lesson_id', $lesson->id)->firstOrFail();
        $data = $request->validate(['outputs' => ['nullable', 'array'], 'styles' => ['nullable', 'array'], 'publish' => ['boolean']]);

        $result = $grader->grade($exercise, $exercise->mode === 'parsons' ? implode("\n", $exercise->parsonsLines()) : $exercise->solution_code, $data['outputs'] ?? [], $data['styles'] ?? []);

        if ($result['status'] !== 'passed') {
            $exercise->update(['verified_hash' => null, 'verified_at' => null]);

            return back()->with('error', "Solusi hanya lulus {$result['passed_tests']} dari {$result['total_tests']} tes. Periksa tes atau solusinya.")
                ->with('verification', $result);
        }

        $exercise->update(['verified_hash' => $exercise->contentHash(), 'verified_at' => now()]);
        if ($request->boolean('publish')) {
            $lesson->update(['is_published' => true]);
        }

        return back()->with('success', 'Solusi lulus semua tes. '.($lesson->fresh()->is_published ? 'Soal sudah terbit.' : 'Soal siap diterbitkan.'))
            ->with('verification', $result);
    }

    /** Siswa mengumpulkan jawaban. */
    public function submit(Request $request, Lesson $lesson, Grader $grader): RedirectResponse
    {
        Gate::authorize('learn', $lesson);
        $exercise = CodeExercise::with('tests')->where('lesson_id', $lesson->id)->firstOrFail();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20000'],
            'outputs' => ['nullable', 'array'],
            'styles' => ['nullable', 'array'],
            'hints_used' => ['nullable', 'integer', 'min:0', 'max:5'],
        ], [], ['code' => 'kode']);

        $result = $grader->grade($exercise, $data['code'], $data['outputs'] ?? [], $data['styles'] ?? []);

        CodeSubmission::create($result + [
            'lesson_id' => $lesson->id,
            'student_id' => $request->user()->id,
            'code' => $data['code'],
            'hints_used' => $data['hints_used'] ?? 0,
            'tests_hash' => $exercise->contentHash(),
            'exercise_version' => $exercise->version,
        ]);

        if ($result['status'] === 'passed') {
            LessonProgress::markDone($lesson->id, $request->user()->id);
        }

        return back()->with($result['status'] === 'passed' ? 'success' : 'error', match ($result['status']) {
            'passed' => 'Semua tes lulus. Keren! 🎉',
            'error' => 'Ada error saat kode dijalankan. Lihat pesannya di bawah.',
            default => "Lulus {$result['passed_tests']} dari {$result['total_tests']} tes. Sedikit lagi!",
        });
    }

    private function saveExercise(Lesson $lesson, ExerciseRequest $request): void
    {
        $data = $request->validated();
        $before = CodeExercise::where('lesson_id', $lesson->id)->first();
        $oldHash = $before?->contentHash();
        $oldSnapshot = $before?->snapshot();

        $exercise = CodeExercise::updateOrCreate(['lesson_id' => $lesson->id], [
            'language' => $data['language'],
            'mode' => $data['mode'],
            'starter_code' => $data['starter_code'] ?? null,
            'solution_code' => $data['solution_code'],
            'function_name' => $data['mode'] === 'function' ? $data['function_name'] : null,
            'hints' => array_values(array_filter($data['hints'] ?? [], fn ($h) => filled($h))),
            'time_limit_ms' => $data['time_limit_ms'],
            'show_solution_after' => $data['show_solution_after'] ?? null,
            'case_sensitive' => $request->boolean('case_sensitive'),
            'rules' => collect($data['rules'] ?? [])->map(fn ($r) => ['type' => $r['type'], 'value' => trim($r['value']), 'message' => $r['message'] ?? null])->values()->all() ?: null,
            'concept' => filled($data['concept'] ?? null) ? mb_strtolower(trim($data['concept'])) : null,
            'level' => $data['level'] ?? null,
        ]);

        $keep = [];
        foreach (array_values($data['mode'] === 'parsons' ? [] : ($data['tests'] ?? [])) as $position => $test) {
            $attributes = [
                'position' => $position + 1,
                'kind' => $test['kind'],
                'input' => $test['input'] ?? null,
                'expected' => $test['expected'] ?? null,
                'property' => $test['property'] ?? null,
                'is_hidden' => $data['mode'] !== 'html' && ! empty($test['is_hidden']),
                'weight' => $test['weight'],
                'fail_message' => $test['fail_message'] ?? null,
            ];
            $existing = ! empty($test['id']) ? $exercise->tests()->find($test['id']) : null;
            $keep[] = $existing ? tap($existing)->update($attributes)->id : $exercise->tests()->create($attributes)->id;
        }
        $exercise->tests()->whereNotIn('id', $keep)->delete();

        $this->recordVersion($exercise->refresh(), $oldHash, $oldSnapshot, $request->user()->id);
    }

    /**
     * Versi soal: bila isi yang dinilai berubah setelah ada siswa yang mengumpulkan,
     * buat versi baru. Nilai lama tetap tercatat pada versi lama.
     */
    private function recordVersion(CodeExercise $exercise, ?string $oldHash, ?array $oldSnapshot, int $userId): void
    {
        if ($oldHash === null) {
            $exercise->versions()->create(['version' => 1, 'snapshot' => $exercise->snapshot(), 'created_by' => $userId]);

            return;
        }

        if ($oldHash === $exercise->contentHash()) {
            return;
        }

        $used = CodeSubmission::where('lesson_id', $exercise->lesson_id)->where('exercise_version', $exercise->version)->exists();
        if ($used) {
            // Soal lama (dibuat sebelum ada riwayat versi) disimpan dulu salinannya.
            $exercise->versions()->firstOrCreate(['version' => $exercise->version], ['snapshot' => $oldSnapshot, 'created_by' => $userId]);
            $exercise->increment('version');
        }

        $exercise->versions()->updateOrCreate(['version' => $exercise->version], ['snapshot' => $exercise->snapshot(), 'created_by' => $userId]);
    }

    /** Soal yang belum lulus "Uji solusi" disimpan sebagai draf, lalu tutor diarahkan untuk mengujinya. */
    private function afterSave(Lesson $lesson, ExerciseRequest $request): RedirectResponse
    {
        $exercise = CodeExercise::where('lesson_id', $lesson->id)->first();

        if ($exercise->isVerified()) {
            return to_route('learning.lessons.show', $lesson)->with('success', 'Soal coding disimpan.');
        }

        $wantsPublish = $request->boolean('is_published');
        $lesson->update(['is_published' => false]);

        return redirect()->route('coding.exercises.edit', ['lesson' => $lesson, 'verify' => 1, 'publish' => $wantsPublish ? 1 : 0])
            ->with('success', 'Soal disimpan sebagai draf. Menjalankan Uji solusi…');
    }

    private function lessonData(ExerciseRequest $request): array
    {
        return [
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'duration_minutes' => $request->validated('duration_minutes'),
            'is_published' => $request->boolean('is_published'),
        ];
    }

    private function options(): array
    {
        return [
            'languages' => CodeExercise::LANGUAGES,
            'modes' => CodeExercise::MODES,
            'modesByLanguage' => CodeExercise::MODES_BY_LANGUAGE,
            'htmlRules' => CodeExercise::HTML_RULES,
            'runtime' => ['driver' => config('coding.driver'), 'pyodide_url' => config('coding.pyodide_url')],
            'autoVerify' => false,
            'autoPublish' => false,
        ];
    }
}
