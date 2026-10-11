<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Identity\Enums\Role;
use Modules\Reports\Actions\MonthlyReport;
use Modules\Reports\Actions\ReportAccess;
use Modules\Reports\Models\MonthlyReportNote;

class MonthlyReportController extends Controller
{
    public function __construct(private ReportAccess $access) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $month = $this->month($request->query('bulan') ?? self::defaultMonth());
        $filters = $request->only('search', 'class');
        $staff = $user->hasAnyRole([Role::Admin->value, Role::Teacher->value]);

        $students = $this->access->students($user)
            ->where('status', 'active')
            ->with('studentProfile:id,user_id,student_code,grade')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($filters['class'] ?? null, fn ($q, $c) => $q->whereIn('id', DB::table('class_students')->where('class_room_id', $c)->select('student_id')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $ids = $students->pluck('id');
        $from = $month->startOfMonth();
        $attended = DB::table('session_attendances as a')->join('class_sessions as s', 's.id', '=', 'a.class_session_id')
            ->whereIn('a.student_id', $ids)->where('s.status', 'completed')->whereBetween('s.starts_at', [$from, $from->endOfMonth()])
            ->groupBy('a.student_id')->selectRaw("a.student_id, count(*) as total, sum(case when a.status = 'present' then 1 else 0 end) as present")
            ->get()->keyBy('student_id');
        $classes = DB::table('class_students')->join('class_rooms', 'class_rooms.id', '=', 'class_students.class_room_id')
            ->whereIn('class_students.student_id', $ids)
            ->when($user->hasRole(Role::Teacher->value) && ! $user->hasRole(Role::Admin->value), fn ($q) => $q->where('class_rooms.teacher_id', $user->id))
            ->get(['class_students.student_id', 'class_rooms.id', 'class_rooms.name'])->groupBy('student_id');
        $notes = MonthlyReportNote::whereIn('student_id', $ids)->whereDate('month', $from->toDateString())
            ->get(['student_id', 'class_room_id'])->groupBy('student_id');

        return Inertia::render('Reports::Index', [
            'month' => ['key' => $from->format('Y-m'), 'label' => $from->translatedFormat('F Y')],
            'months' => $this->recentMonths(),
            'filters' => $filters,
            'staff' => $staff,
            'classOptions' => $staff
                ? ClassRoom::query()->when(! $user->hasRole(Role::Admin->value), fn ($q) => $q->where('teacher_id', $user->id))->orderBy('name')->get(['id', 'name'])
                : [],
            'students' => $students->through(function (User $s) use ($attended, $classes, $notes) {
                $mine = $classes->get($s->id, collect());
                $written = $notes->get($s->id, collect())->pluck('class_room_id');

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'initials' => $s->initials(),
                    'code' => $s->studentProfile?->student_code,
                    'grade' => $s->studentProfile?->grade,
                    'classes' => $mine->pluck('name')->unique()->values(),
                    'sessions' => (int) ($attended[$s->id]->total ?? 0),
                    'present' => (int) ($attended[$s->id]->present ?? 0),
                    'notes_missing' => $mine->pluck('id')->unique()->diff($written)->count(),
                ];
            }),
        ]);
    }

    public function show(Request $request, User $student, string $month, MonthlyReport $report): Response
    {
        abort_unless($student->hasRole(Role::Student->value) && $this->access->canView($request->user(), $student), 403);
        $month = $this->month($month);

        $data = $report->build($student, $month);
        $noteable = $this->access->noteableClassIds($request->user(), $student);

        return Inertia::render('Reports::Show', [
            'report' => $data,
            'editable' => collect($data['classes'])->whereIn('id', $noteable)->values(),
            'prev' => $month->subMonth()->format('Y-m'),
            'next' => $month->addMonth()->startOfMonth()->isFuture() ? null : $month->addMonth()->format('Y-m'),
        ]);
    }

    public function note(Request $request, User $student, string $month): RedirectResponse
    {
        $data = $request->validate([
            'class_room_id' => ['required', 'integer'],
            'highlights' => ['nullable', 'string', 'max:2000'],
            'focus' => ['nullable', 'string', 'max:2000'],
        ], [], ['highlights' => 'yang sudah bagus', 'focus' => 'fokus bulan depan']);

        abort_unless(in_array((int) $data['class_room_id'], $this->access->noteableClassIds($request->user(), $student), true), 403);

        $month = $this->month($month);
        $note = MonthlyReportNote::where('student_id', $student->id)->where('class_room_id', $data['class_room_id'])
            ->whereDate('month', $month->toDateString())->first()
            ?? new MonthlyReportNote(['student_id' => $student->id, 'class_room_id' => $data['class_room_id'], 'month' => $month]);
        $note->fill(['highlights' => $data['highlights'], 'focus' => $data['focus'], 'author_id' => $request->user()->id])->save();

        return back()->with('success', 'Catatan bulanan disimpan.');
    }

    /** Minggu pertama bulan baru masih menampilkan laporan bulan lalu. */
    public static function defaultMonth(): string
    {
        return (now()->day <= 7 ? now()->subMonthNoOverflow() : now())->format('Y-m');
    }

    private function month(string $value): CarbonImmutable
    {
        abort_unless(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value), 404);
        $month = CarbonImmutable::createFromFormat('!Y-m', $value)->startOfMonth();
        abort_if($month->isAfter(now()->startOfMonth()), 404);

        return $month;
    }

    private function recentMonths(): array
    {
        return collect(range(0, 11))->map(fn ($i) => now()->startOfMonth()->subMonthsNoOverflow($i))
            ->map(fn ($m) => ['key' => $m->format('Y-m'), 'label' => $m->translatedFormat('F Y')])->all();
    }
}
