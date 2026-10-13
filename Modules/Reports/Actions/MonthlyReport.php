<?php

namespace Modules\Reports\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\StudentPackage;
use Modules\Gamification\Actions\Achievements;
use Modules\Gamification\Models\XpEvent;
use Modules\Gamification\Support\Levels;
use Modules\Learning\Actions\StudentProgress;
use Modules\Reports\Models\MonthlyReportNote;
use Modules\Sessions\Models\SessionAttendance;

/**
 * One month of a student's learning summarised for parents: attendance,
 * per-session notes, lessons and scores, projects, achievements and teacher notes.
 * Sections from inactive modules are skipped.
 */
class MonthlyReport
{
    public function __construct(private StudentProgress $progress) {}

    public function build(User $student, CarbonImmutable $month): array
    {
        $from = $month->startOfMonth();
        $to = $month->endOfMonth();

        $sessions = $this->sessions($student, $from, $to);
        $quizzes = $this->quizzes($student, $from, $to);

        return [
            'month' => ['key' => $from->format('Y-m'), 'label' => $from->translatedFormat('F Y'), 'is_current' => $from->isSameMonth(now())],
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'initials' => $student->initials(),
                'code' => $student->studentProfile?->student_code,
                'grade' => $student->studentProfile?->grade,
                'school' => $student->studentProfile?->school,
            ],
            'classes' => DB::table('class_students')
                ->join('class_rooms', 'class_rooms.id', '=', 'class_students.class_room_id')
                ->leftJoin('users as t', 't.id', '=', 'class_rooms.teacher_id')
                ->where('class_students.student_id', $student->id)
                ->orderBy('class_rooms.name')
                ->get(['class_rooms.id', 'class_rooms.name', 'class_rooms.subject', 't.name as teacher'])
                ->map(fn ($c) => (array) $c)->all(),
            'attendance' => $this->attendanceSummary($sessions),
            'sessions' => $sessions,
            'lessons' => $this->lessons($student, $from, $to),
            'quizzes' => $quizzes,
            'quiz_avg' => $quizzes ? (int) round(collect($quizzes)->avg('score')) : null,
            'assignments' => $this->assignments($student, $from, $to),
            'code' => $this->code($student, $from, $to),
            'projects' => $this->projects($student, $from, $to),
            'achievements' => $this->achievements($student, $from, $to),
            'certificates' => $this->certificates($student, $from, $to),
            'courses' => $this->progress->forStudent($student)->map(fn (array $c) => [
                'title' => $c['title'], 'percent' => $c['percent'], 'completed' => $c['completed'], 'total' => $c['total'],
            ])->values()->all(),
            'notes' => MonthlyReportNote::query()
                ->where('student_id', $student->id)->whereDate('month', $from->toDateString())
                ->with('author:id,name')
                ->get()
                ->map(fn (MonthlyReportNote $n) => [
                    'class_room_id' => $n->class_room_id,
                    'highlights' => $n->highlights,
                    'focus' => $n->focus,
                    'author' => $n->author?->name,
                ])->keyBy('class_room_id')->all(),
            'account' => $this->account($student),
        ];
    }

    private function sessions(User $student, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('session_attendances as a')
            ->join('class_sessions as s', 's.id', '=', 'a.class_session_id')
            ->join('class_rooms as c', 'c.id', '=', 's.class_room_id')
            ->leftJoin('users as t', 't.id', '=', 's.teacher_id')
            ->where('a.student_id', $student->id)
            ->where('s.status', 'completed')
            ->whereBetween('s.starts_at', [$from, $to])
            ->orderBy('s.starts_at')
            ->get(['s.starts_at', 'c.name as class', 't.name as teacher', 's.topic', 's.homework', 's.parent_note', 'a.status', 'a.note'])
            ->map(fn ($r) => [
                'date' => Carbon::parse($r->starts_at)->translatedFormat('D, d M'),
                'time' => Carbon::parse($r->starts_at)->format('H:i'),
                'class' => $r->class,
                'teacher' => $r->teacher,
                'topic' => $r->topic,
                'homework' => $r->homework,
                'parent_note' => $r->parent_note,
                'note' => $r->note,
                'status' => $r->status,
                'status_label' => SessionAttendance::STATUSES[$r->status] ?? $r->status,
            ])->all();
    }

    private function attendanceSummary(array $sessions): array
    {
        $count = collect($sessions)->countBy('status');
        $total = count($sessions);

        return [
            'total' => $total,
            'present' => $count->get('present', 0),
            'excused' => $count->get('excused', 0),
            'sick' => $count->get('sick', 0),
            'absent' => $count->get('absent', 0),
            'rate' => $total ? (int) round($count->get('present', 0) / $total * 100) : null,
        ];
    }

    private function lessons(User $student, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('lesson_progress as p')
            ->join('lessons as l', 'l.id', '=', 'p.lesson_id')
            ->join('courses as c', 'c.id', '=', 'l.course_id')
            ->where('p.student_id', $student->id)
            ->whereBetween('p.completed_at', [$from, $to])
            ->orderBy('p.completed_at')
            ->get(['l.title', 'l.type', 'c.title as course', 'p.completed_at'])
            ->map(fn ($r) => ['title' => $r->title, 'type' => $r->type, 'course' => $r->course, 'date' => Carbon::parse($r->completed_at)->translatedFormat('d M')])
            ->all();
    }

    /** Best score per quiz taken this month. */
    private function quizzes(User $student, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('quiz_attempts as q')
            ->join('lessons as l', 'l.id', '=', 'q.lesson_id')
            ->where('q.student_id', $student->id)
            ->whereBetween('q.created_at', [$from, $to])
            ->groupBy('q.lesson_id', 'l.title')
            ->orderBy('l.title')
            ->selectRaw('l.title, max(q.score) as score, count(*) as tries, max(case when q.passed then 1 else 0 end) as passed')  // max(boolean) does not exist in PostgreSQL
            ->get()
            ->map(fn ($r) => ['title' => $r->title, 'score' => (int) $r->score, 'tries' => (int) $r->tries, 'passed' => (bool) $r->passed])
            ->all();
    }

    private function assignments(User $student, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('assignment_submissions as s')
            ->join('lessons as l', 'l.id', '=', 's.lesson_id')
            ->where('s.student_id', $student->id)
            ->where('s.status', 'graded')
            ->whereBetween('s.graded_at', [$from, $to])
            ->orderBy('s.graded_at')
            ->get(['l.title', 's.score', 'l.max_score', 's.feedback'])
            ->map(fn ($r) => ['title' => $r->title, 'score' => $r->score, 'max' => $r->max_score ?: 100, 'feedback' => $r->feedback])
            ->all();
    }

    /** Coding exercises first solved this month. */
    private function code(User $student, CarbonImmutable $from, CarbonImmutable $to): ?array
    {
        if (! Schema::hasTable('code_submissions')) {
            return null;
        }

        return DB::table('code_submissions as s')
            ->join('lessons as l', 'l.id', '=', 's.lesson_id')
            ->where('s.student_id', $student->id)
            ->where('s.status', 'passed')
            ->groupBy('s.lesson_id', 'l.title')
            ->havingBetween(DB::raw('min(s.created_at)'), [$from, $to])
            ->orderBy('l.title')
            ->pluck('l.title')
            ->all();
    }

    private function projects(User $student, CarbonImmutable $from, CarbonImmutable $to): ?array
    {
        if (! Route::has('kanban.projects.index')) {
            return null;
        }

        return DB::table('project_cards as k')
            ->join('project_boards as b', 'b.id', '=', 'k.board_id')
            ->join('projects as p', 'p.id', '=', 'b.project_id')
            ->where('b.student_id', $student->id)
            ->where('k.column', 'done')
            ->whereBetween('k.done_at', [$from, $to])
            ->orderBy('k.done_at')
            ->get(['k.title', 'p.title as project'])
            ->map(fn ($r) => (array) $r)->all();
    }

    private function achievements(User $student, CarbonImmutable $from, CarbonImmutable $to): ?array
    {
        if (! Route::has('gamification.me')) {
            return null;
        }

        $events = XpEvent::where('user_id', $student->id)->orderBy('occurred_at')->get(['rule', 'source_id', 'points', 'occurred_at']);

        return [
            'xp' => (int) $events->whereBetween('occurred_at', [$from, $to])->sum('points'),
            'level' => Levels::for((int) $events->where('occurred_at', '<=', $to)->sum('points')),
            'badges' => collect(app(Achievements::class)->badges($events))
                ->filter(fn ($b) => $b['earned_ts'] && $b['earned_ts'] >= $from->timestamp && $b['earned_ts'] <= $to->timestamp)
                ->map(fn ($b) => ['emoji' => $b['emoji'], 'name' => $b['name'], 'how' => $b['how']])
                ->values()->all(),
        ];
    }

    private function certificates(User $student, CarbonImmutable $from, CarbonImmutable $to): array
    {
        if (! Schema::hasTable('certificates')) {
            return [];
        }

        return DB::table('certificates')
            ->where('student_id', $student->id)->whereNull('revoked_at')
            ->whereBetween('issued_at', [$from->toDateString(), $to->toDateString()])
            ->get(['title', 'number'])->map(fn ($r) => (array) $r)->all();
    }

    /** Session packages and invoices as of today, so parents know what to prepare. */
    private function account(User $student): array
    {
        return [
            'packages' => StudentPackage::where('student_id', $student->id)->where('status', 'active')
                ->withCount('usages')->get()
                ->map(fn (StudentPackage $p) => ['name' => $p->name, 'remaining' => $p->remaining(), 'total' => $p->sessions_total, 'expires_at' => $p->expires_at?->translatedFormat('d M Y')])
                ->all(),
            'outstanding' => (int) Invoice::where('student_id', $student->id)->outstanding()->get()->sum(fn (Invoice $i) => $i->remaining()),
        ];
    }
}
