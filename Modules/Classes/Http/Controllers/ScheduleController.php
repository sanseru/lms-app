<?php

namespace Modules\Classes\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $week = $request->date('week') ?? now();
        $start = Carbon::parse($week)->startOfWeek();

        $classes = ClassRoom::query()
            ->visibleTo($request->user())
            ->where('status', 'active')
            ->with('teacher:id,name')
            ->withCount('students')
            ->get();

        $days = collect(range(0, 6))->map(function (int $offset) use ($start, $classes) {
            $date = $start->copy()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'day' => ClassRoom::DAYS[$date->dayOfWeekIso],
                'label' => $date->translatedFormat('d M'),
                'is_today' => $date->isToday(),
                'sessions' => $classes
                    ->filter(fn (ClassRoom $c) => in_array($date->dayOfWeekIso, $c->schedule_days)
                        && (! $c->start_date || $c->start_date->lte($date))
                        && (! $c->end_date || $c->end_date->gte($date)))
                    ->sortBy('start_time')
                    ->values()
                    ->map(fn (ClassRoom $c) => $c->toCard()),
            ];
        });

        return Inertia::render('Classes::Schedule/Index', [
            'days' => $days,
            'week' => [
                'start' => $start->toDateString(),
                'label' => $start->translatedFormat('d M').' – '.$start->copy()->endOfWeek()->translatedFormat('d M Y'),
                'prev' => $start->copy()->subWeek()->toDateString(),
                'next' => $start->copy()->addWeek()->toDateString(),
            ],
        ]);
    }
}
