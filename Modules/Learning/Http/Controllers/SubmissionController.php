<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Learning\Http\Requests\GradeRequest;
use Modules\Learning\Http\Requests\SubmissionRequest;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    /** Siswa mengumpulkan atau mengumpulkan ulang (setelah diminta revisi). */
    public function store(SubmissionRequest $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();
        $submission = $lesson->submissions()->firstOrNew(['student_id' => $user->id]);

        if ($file = $request->file('file')) {
            if ($submission->file_path) {
                Storage::disk('local')->delete($submission->file_path);
            }
            $submission->file_path = $file->store("submissions/{$lesson->id}", 'local');
            $submission->file_name = $file->getClientOriginalName();
        }

        $submission->fill([
            'body' => $request->validated('body'),
            'link_url' => $request->validated('link_url'),
            'status' => 'submitted',
            'submitted_at' => now(),
            'score' => null,
        ])->save();

        LessonProgress::markDone($lesson->id, $user->id);

        return back()->with('success', 'Tugas terkumpul. Guru akan menilainya.');
    }

    public function grade(GradeRequest $request, AssignmentSubmission $submission): RedirectResponse
    {
        $data = $request->validated();

        $submission->update([
            'status' => $data['status'],
            'score' => $data['status'] === 'graded' ? $data['score'] : null,
            'feedback' => $data['feedback'] ?? null,
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        return back()->with('success', $data['status'] === 'graded' ? 'Nilai tersimpan.' : 'Siswa diminta merevisi tugas.');
    }

    public function file(AssignmentSubmission $submission): StreamedResponse
    {
        Gate::authorize('view', $submission);
        abort_unless($submission->file_path && Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->file_name);
    }
}
