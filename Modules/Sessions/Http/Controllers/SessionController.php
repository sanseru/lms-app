<?php

namespace Modules\Sessions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Sessions\Actions\CompleteSession;
use Modules\Sessions\Actions\GenerateSessions;
use Modules\Sessions\Actions\RescheduleSession;
use Modules\Sessions\Http\Requests\RescheduleRequest;
use Modules\Sessions\Http\Requests\SessionReportRequest;
use Modules\Sessions\Models\ClassSession;
use Modules\Sessions\Models\SessionAttendance;

class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab = $request->input('tab', 'needs_report');

        $base = ClassSession::query()
            ->visibleTo($user)
            ->when(! $user->can('classes.manage'), fn ($q) => $q->where('teacher_id', $user->id));

        $sessions = (clone $base)
            ->with(['classRoom:id,name,subject,color,zoom_url', 'teacher:id,name'])
            ->when($tab === 'needs_report', fn ($q) => $q->needsReport()->orderBy('starts_at'))
            ->when($tab === 'upcoming', fn ($q) => $q->where('status', 'scheduled')->where('ends_at', '>=', now())->orderBy('starts_at'))
            ->when($tab === 'completed', fn ($q) => $q->where('status', 'completed')->orderByDesc('starts_at'))
            ->when($tab === 'flagged', fn ($q) => $q->where('flagged', true)->orderByDesc('starts_at'))
            ->when($tab === 'cancelled', fn ($q) => $q->whereIn('status', ['cancelled', 'rescheduled'])->orderByDesc('starts_at'))
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ClassSession $s) => $s->toItem());

        return Inertia::render('Sessions::Sessions/Index', [
            'sessions' => $sessions,
            'tab' => $tab,
            'counts' => [
                'needs_report' => (clone $base)->needsReport()->count(),
                'flagged' => (clone $base)->where('flagged', true)->count(),
            ],
        ]);
    }

    public function show(Request $request, ClassSession $session): Response
    {
        Gate::authorize('view', $session);
        $session->load(['classRoom.students.studentProfile', 'teacher:id,name', 'attendances', 'replaces', 'replacement']);
        $user = $request->user();
        $canReport = $session->isReportable() && $user->can('report', $session);
        $isStaff = $canReport || $user->can('classes.manage');

        $attendance = $session->attendances->keyBy('student_id');
        $visibleStudentIds = $isStaff ? null : collect([$user->id])->merge($user->children()->pluck('users.id'));

        return Inertia::render('Sessions::Sessions/Show', [
            'session' => [
                ...$session->toItem(),
                'zoom_url' => $session->zoomLink(),
                'homework' => $session->homework,
                'parent_note' => $session->parent_note,
                'internal_note' => $isStaff ? $session->internal_note : null,
                'recording_url' => $session->recording_url,
                'cancel_reason' => $session->cancel_reason,
                'completed_at' => $session->completed_at?->translatedFormat('d M Y H:i'),
                'replaces' => $session->replaces ? self::link($session->replaces) : null,
                'replacement' => $session->replacement ? self::link($session->replacement) : null,
            ],
            'students' => $session->classRoom->students
                ->when($visibleStudentIds, fn ($c) => $c->whereIn('id', $visibleStudentIds))
                ->sortBy('name')->values()
                ->map(fn (User $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'initials' => $s->initials(),
                    'code' => $s->studentProfile?->student_code,
                    'status' => $attendance[$s->id]->status ?? null,
                    'note' => $attendance[$s->id]->note ?? null,
                ]),
            'statuses' => SessionAttendance::STATUSES,
            'can' => [
                'report' => $canReport,
                'cancel' => $session->status === 'scheduled' && $user->can('cancel', $session),
                'reschedule' => $session->canBeRescheduled() && $user->can('reschedule', $session),
                'staff' => $isStaff,
            ],
        ]);
    }

    public function update(SessionReportRequest $request, ClassSession $session, CompleteSession $complete): RedirectResponse
    {
        $wasCompleted = $session->status === 'completed';
        $complete->handle($session, $request->validated(), $request->user());

        return to_route('sessions.show', $session)->with('success', $wasCompleted ? 'Laporan sesi diperbarui.' : 'Sesi selesai. Absensi dan catatan tersimpan.');
    }

    public function cancel(Request $request, ClassSession $session): RedirectResponse
    {
        Gate::authorize('cancel', $session);
        abort_unless($session->status === 'scheduled', 422);

        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'alasan']);
        $session->update(['status' => 'cancelled', 'cancel_reason' => $data['reason']]);

        return back()->with('success', 'Sesi dibatalkan. Kuota siswa tidak terpotong.');
    }

    /** Pindah jadwal sesi, atau buat sesi pengganti untuk sesi yang dibatalkan. */
    public function reschedule(RescheduleRequest $request, ClassSession $session, RescheduleSession $reschedule): RedirectResponse
    {
        $data = $request->validated();
        $new = $reschedule->handle(
            $session,
            Carbon::parse("{$data['date']} {$data['start_time']}"),
            Carbon::parse("{$data['date']} {$data['end_time']}"),
            $data['reason'],
        );

        return to_route('sessions.show', $new)->with('success', 'Sesi pengganti dibuat '.$new->starts_at->translatedFormat('l, d M Y H:i').'.');
    }

    private static function link(ClassSession $s): array
    {
        return [
            'id' => $s->id,
            'label' => $s->starts_at->translatedFormat('l, d M Y').' · '.$s->starts_at->format('H:i').'–'.$s->ends_at->format('H:i'),
            'status_label' => ClassSession::STATUSES[$s->status],
        ];
    }

    public function generate(Request $request, ClassRoom $class, GenerateSessions $generator): RedirectResponse
    {
        Gate::authorize('update', $class);
        $data = $request->validate(['weeks' => ['required', 'integer', 'min:1', 'max:26']]);

        $count = $generator->forClass($class, now(), now()->addWeeks($data['weeks']));

        return back()->with('success', "{$count} sesi baru dibuat.");
    }
}
