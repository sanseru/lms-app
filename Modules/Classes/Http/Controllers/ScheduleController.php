<?php

namespace Modules\Classes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Sessions\Models\ClassSession;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $start = Carbon::parse($request->date('week') ?? now())->startOfWeek();
        $end = $start->copy()->endOfWeek();
        $user = $request->user();

        $sessions = ClassSession::query()
            ->visibleTo($user)
            ->whereBetween('starts_at', [$start, $end])
            ->with(['classRoom:id,name,subject,color,zoom_url', 'teacher:id,name'])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (ClassSession $s) => $s->starts_at->toDateString());

        $days = collect(range(0, 6))->map(function (int $offset) use ($start, $sessions) {
            $date = $start->copy()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'day' => ClassRoom::DAYS[$date->dayOfWeekIso],
                'label' => $date->translatedFormat('d M'),
                'is_today' => $date->isToday(),
                'sessions' => ($sessions[$date->toDateString()] ?? collect())->map->toItem()->values(),
            ];
        });

        return Inertia::render('Classes::Schedule/Index', [
            'days' => $days,
            'highlights' => $user->hasAnyRole(['student', 'guardian']) ? $this->highlights($user) : null,
            'calendarUrl' => $user->calendar_token ? route('calendar.feed', $user->calendar_token) : null,
            'week' => [
                'start' => $start->toDateString(),
                'label' => $start->translatedFormat('d M').' – '.$end->translatedFormat('d M Y'),
                'prev' => $start->copy()->subWeek()->toDateString(),
                'next' => $start->copy()->addWeek()->toDateString(),
            ],
        ]);
    }

    /**
     * The three cards of the client's Online Lesson page, from real sessions:
     * the class that is on now (or starts within 10 minutes), the next one, and the
     * latest class with a Zoom recording.
     */
    private function highlights(User $user): array
    {
        $scheduled = fn () => ClassSession::query()->visibleTo($user)->where('status', 'scheduled')->with(['classRoom:id,name,subject,color,zoom_url', 'teacher:id,name']);
        $card = fn (?ClassSession $s) => $s ? $s->toItem() + ['minutes' => $s->durationMinutes(), 'recording_url' => $s->recording_url, 'this_week' => $s->starts_at->isCurrentWeek()] : null;

        $live = $scheduled()->where('starts_at', '<=', now()->addMinutes(10))->where('ends_at', '>=', now())->orderBy('starts_at')->first();
        $next = $scheduled()->where('starts_at', '>', now()->addMinutes(10))->orderBy('starts_at')->first();
        $recording = ClassSession::query()->visibleTo($user)->where('status', 'completed')->whereNotNull('recording_url')
            ->with(['classRoom:id,name,subject,color', 'teacher:id,name'])->latest('starts_at')->first();

        return ['live' => $card($live), 'next' => $card($next), 'recording' => $card($recording)];
    }
}
