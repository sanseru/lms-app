<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Enums\Role;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonReport;
use Modules\Notifications\Support\Notify;

/** The "Report a problem" button on lesson pages and the teacher fix queue. */
class LessonReportController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'open');

        $reports = LessonReport::query()
            ->reviewableBy($request->user())
            ->with(['lesson:id,title,type,course_id', 'lesson.course:id,title', 'user:id,name', 'resolver:id,name'])
            ->where('status', $status === 'open' ? '=' : '!=', 'open')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (LessonReport $r) => [
                'id' => $r->id,
                'lesson' => ['id' => $r->lesson->id, 'title' => $r->lesson->title, 'type' => $r->lesson->type, 'course' => $r->lesson->course?->title],
                'reason' => LessonReport::REASONS[$r->reason] ?? $r->reason,
                'message' => $r->message,
                'student' => $r->user->name,
                'status' => $r->status,
                'status_label' => LessonReport::STATUSES[$r->status],
                'response' => $r->response,
                'resolver' => $r->resolver?->name,
                'at' => $r->created_at->diffForHumans(),
            ]);

        return Inertia::render('Learning::Reports/Index', [
            'reports' => $reports,
            'status' => $status,
            'openCount' => LessonReport::reviewableBy($request->user())->where('status', 'open')->count(),
        ]);
    }

    public function store(Request $request, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $lesson);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(LessonReport::REASONS))],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $already = LessonReport::where('lesson_id', $lesson->id)->where('user_id', $request->user()->id)->where('status', 'open')->exists();
        if ($already) {
            return back()->with('success', 'Thanks, your teacher already has your report for this lesson.');
        }

        $report = LessonReport::create($data + ['lesson_id' => $lesson->id, 'user_id' => $request->user()->id]);

        Notify::send(
            $this->reviewers($lesson),
            "Problem reported: {$lesson->title}",
            $request->user()->name.': '.LessonReport::REASONS[$report->reason].($report->message ? " – {$report->message}" : ''),
            route('learning.reports.index'),
            'flag',
            "lesson-report:{$report->id}",
        );

        return back()->with('success', 'Thanks! Your teacher will check it.');
    }

    public function resolve(Request $request, LessonReport $report): RedirectResponse
    {
        abort_unless(LessonReport::reviewableBy($request->user())->whereKey($report->id)->exists(), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['resolved', 'dismissed'])],
            'response' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update($data + ['resolved_by' => $request->user()->id, 'resolved_at' => now()]);
        $report->load('lesson:id,title', 'user');

        Notify::send(
            $report->user,
            $data['status'] === 'resolved' ? "Fixed: {$report->lesson->title}" : "Checked: {$report->lesson->title}",
            $data['response'] ?: ($data['status'] === 'resolved' ? 'Thanks for reporting. The lesson has been fixed.' : 'Your teacher checked the lesson and it is correct as it is.'),
            route('learning.lessons.show', $report->lesson_id),
            'flag',
            "lesson-report-done:{$report->id}",
        );

        return back()->with('success', 'Report closed and the student was told.');
    }

    /** Admins, the course creator and teachers of classes using this course. */
    private function reviewers(Lesson $lesson)
    {
        $teacherIds = DB::table('class_rooms')->where('course_id', $lesson->course_id)->whereNotNull('teacher_id')->pluck('teacher_id');
        $creator = DB::table('courses')->where('id', $lesson->course_id)->value('created_by');

        return User::query()
            ->where(fn ($q) => $q->whereIn('id', $teacherIds->push($creator)->filter())->orWhereHas('roles', fn ($q) => $q->where('name', Role::Admin->value)))
            ->get();
    }
}
