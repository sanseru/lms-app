<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PayrollItem;
use Modules\Finance\Models\StudentPackage;
use Modules\Identity\Enums\Role;
use Modules\Learning\Actions\StudentProgress;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Sessions\Models\ClassSession;
use Modules\Sessions\Models\SessionAttendance;

/**
 * Satu URL /dashboard, tampilan berbeda per role utama user.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return match ($user->primaryRole()) {
            Role::Admin => $this->admin(),
            Role::Teacher => $this->teacher($user),
            Role::Guardian => $this->guardian($user),
            Role::Student => $this->student($user),
            default => Inertia::render('Dashboard::NoRole'),
        };
    }

    private function admin(): Response
    {
        return Inertia::render('Dashboard::Admin', [
            'stats' => [
                'students' => User::role(Role::Student->value)->where('status', 'active')->count(),
                'teachers' => User::role(Role::Teacher->value)->where('status', 'active')->count(),
                'classes' => ClassRoom::where('status', 'active')->count(),
                'outstanding' => (int) Invoice::outstanding()->selectRaw('coalesce(sum(amount - paid_amount), 0) as total')->value('total'),
            ],
            'today' => $this->sessions(ClassSession::query()->whereDate('starts_at', today())),
            'alerts' => [
                'needs_report' => ClassSession::needsReport()->count(),
                'flagged' => ClassSession::where('flagged', true)->where('starts_at', '>=', now()->subDays(14))->count(),
                'low_quota' => $this->lowQuotaCount(),
            ],
            'pendingPayments' => Payment::where('status', 'pending')->with('invoice.student:id,name')->latest()->take(5)->get()
                ->map(fn (Payment $p) => [
                    'id' => $p->id,
                    'invoice_id' => $p->invoice_id,
                    'student' => $p->invoice->student->name,
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->translatedFormat('d M'),
                ]),
            'recentStudents' => User::role(Role::Student->value)->with('studentProfile:id,user_id,student_code')->latest()->take(5)->get()
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'initials' => $u->initials(), 'code' => $u->studentProfile?->student_code, 'joined' => $u->created_at->diffForHumans()]),
        ]);
    }

    private function teacher(User $user): Response
    {
        $classes = $user->teachingClasses()->withCount('students')->orderBy('start_time')->get();
        $mine = fn () => ClassSession::query()->where('teacher_id', $user->id);

        return Inertia::render('Dashboard::Teacher', [
            'stats' => [
                'classes' => $classes->where('status', 'active')->count(),
                'students' => $classes->where('status', 'active')->sum('students_count'),
                'sessions_month' => $mine()->where('status', 'completed')->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'fee_pending' => (int) PayrollItem::where('teacher_id', $user->id)->whereNull('teacher_payout_id')->sum('amount'),
                'to_grade' => AssignmentSubmission::query()->gradableBy($user)->where('status', 'submitted')->count(),
            ],
            'today' => $this->sessions($mine()->whereDate('starts_at', today())),
            'needsReport' => $this->sessions($mine()->needsReport()->orderBy('starts_at')->take(10)),
            'classes' => $classes->map->toCard(),
        ]);
    }

    private function student(User $user): Response
    {
        $classes = $user->enrolledClasses()->with('teacher:id,name')->get();

        return Inertia::render('Dashboard::Student', [
            'upcoming' => $this->sessions(ClassSession::query()
                ->whereIn('class_room_id', $classes->pluck('id'))
                ->where('status', 'scheduled')->where('ends_at', '>=', now())
                ->orderBy('starts_at')->take(4)),
            'lastSession' => $this->lastSessionFor($user),
            'classes' => $classes->map->toCard(),
            'packages' => $this->packagesFor($user),
            'stats' => $this->attendanceStats($user),
            'learning' => $this->learningSummary($user),
            'profile' => [
                'code' => $user->studentProfile?->student_code,
                'grade' => $user->studentProfile?->grade,
            ],
        ]);
    }

    private function guardian(User $user): Response
    {
        $children = $user->children()->with(['studentProfile', 'enrolledClasses'])->get();

        return Inertia::render('Dashboard::Guardian', [
            'children' => $children->map(fn (User $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'initials' => $child->initials(),
                'code' => $child->studentProfile?->student_code,
                'grade' => $child->studentProfile?->grade,
                'packages' => $this->packagesFor($child),
                'stats' => $this->attendanceStats($child),
                'learning' => $this->learningSummary($child),
                'next' => $this->sessions(ClassSession::query()
                    ->whereIn('class_room_id', $child->enrolledClasses->pluck('id'))
                    ->where('status', 'scheduled')->where('ends_at', '>=', now())
                    ->orderBy('starts_at')->take(2)),
                'lastSession' => $this->lastSessionFor($child),
            ]),
            'invoices' => Invoice::visibleTo($user)->outstanding()->with('student:id,name')->orderBy('due_date')->take(5)->get()
                ->map(fn (Invoice $i) => [
                    'id' => $i->id,
                    'number' => $i->number,
                    'title' => $i->title,
                    'student' => $i->student->name,
                    'remaining' => $i->remaining(),
                    'due_date' => $i->due_date->translatedFormat('d M Y'),
                    'is_overdue' => $i->isOverdue(),
                ]),
        ]);
    }

    private function sessions($query)
    {
        return $query->with(['classRoom:id,name,subject,color,zoom_url', 'teacher:id,name'])->orderBy('starts_at')->get()->map->toItem();
    }

    private function packagesFor(User $student)
    {
        return StudentPackage::where('student_id', $student->id)
            ->whereIn('status', ['active', 'pending_payment', 'used_up'])
            ->where(fn ($q) => $q->where('status', '!=', 'used_up')->orWhere('updated_at', '>=', now()->subDays(30)))
            ->with('classRoom:id,name')
            ->withCount('usages')
            ->latest()
            ->get()
            ->map->toSummary();
    }

    private function attendanceStats(User $student): array
    {
        $rows = SessionAttendance::where('student_id', $student->id)
            ->whereHas('session', fn ($q) => $q->where('status', 'completed'))
            ->pluck('status');

        return [
            'sessions' => $rows->count(),
            'present' => $rows->filter(fn ($s) => $s === 'present')->count(),
            'rate' => $rows->count() ? (int) round($rows->filter(fn ($s) => $s === 'present')->count() / $rows->count() * 100) : null,
        ];
    }

    /** Sesi terakhir yang dihadiri: materi, PR, dan catatan guru untuk orang tua. */
    private function lastSessionFor(User $student): ?array
    {
        $attendance = SessionAttendance::where('student_id', $student->id)
            ->whereHas('session', fn ($q) => $q->where('status', 'completed'))
            ->with('session.classRoom:id,name,subject,color', 'session.teacher:id,name')
            ->get()
            ->sortByDesc(fn ($a) => $a->session->starts_at)
            ->first();

        if (! $attendance) {
            return null;
        }

        return [
            ...$attendance->session->toItem(),
            'attendance' => SessionAttendance::STATUSES[$attendance->status],
            'attendance_status' => $attendance->status,
            'homework' => $attendance->session->homework,
            'parent_note' => $attendance->session->parent_note,
        ];
    }

    private function lowQuotaCount(): int
    {
        return StudentPackage::where('status', 'active')->withCount('usages')->get()
            ->filter(fn (StudentPackage $p) => $p->sessions_total - $p->usages_count <= 2)
            ->count();
    }

    /** Ringkas progres kursus untuk kartu dashboard. */
    private function learningSummary(User $student): array
    {
        return app(StudentProgress::class)->forStudent($student)
            ->map(fn (array $c) => [
                'id' => $c['id'],
                'title' => $c['title'],
                'subject' => $c['subject'],
                'color' => $c['color'],
                'percent' => $c['percent'],
                'completed' => $c['completed'],
                'total' => $c['total'],
                'quiz_avg' => $c['quiz_avg'],
                'pending_assignments' => $c['pending_assignments'],
                'next' => $c['next'] ? ['id' => $c['next']['id'], 'title' => $c['next']['title'], 'type' => $c['next']['type'], 'type_label' => $c['next']['type_label']] : null,
            ])
            ->values()
            ->all();
    }
}
