<?php

namespace Modules\Classes\Http\Controllers;

use App\Http\Controllers\Controller;
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
            'calendarUrl' => $user->calendar_token ? route('calendar.feed', $user->calendar_token) : null,
            'week' => [
                'start' => $start->toDateString(),
                'label' => $start->translatedFormat('d M').' – '.$end->translatedFormat('d M Y'),
                'prev' => $start->copy()->subWeek()->toDateString(),
                'next' => $start->copy()->addWeek()->toDateString(),
            ],
        ]);
    }
}
