<?php

namespace Modules\Sessions\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\Classes\Models\ClassRoom;
use Modules\Sessions\Models\ClassSession;

/**
 * Creates sessions from a class's weekly schedule for a date range.
 * Existing sessions (same date + time) are skipped.
 */
class GenerateSessions
{
    public function forClass(ClassRoom $class, CarbonInterface $from, CarbonInterface $to): int
    {
        if (! in_array($class->status, ['active', 'upcoming'])) {
            return 0;
        }

        $notBefore = Carbon::parse($from);
        $from = $notBefore->copy()->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        if ($class->start_date && $class->start_date->gt($from)) {
            $from = $class->start_date->copy()->startOfDay();
        }
        if ($class->end_date && $class->end_date->lt($to)) {
            $to = $class->end_date->copy()->endOfDay();
        }

        $created = 0;

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            if (! in_array($date->dayOfWeekIso, $class->schedule_days)) {
                continue;
            }

            $startsAt = $date->copy()->setTimeFromTimeString($class->start_time);

            if ($startsAt->lt($notBefore)) {
                continue;
            }

            $session = ClassSession::firstOrCreate(
                ['class_room_id' => $class->id, 'starts_at' => $startsAt],
                ['ends_at' => $date->copy()->setTimeFromTimeString($class->end_time), 'teacher_id' => $class->teacher_id, 'status' => 'scheduled'],
            );

            $created += $session->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    /** Delete upcoming sessions that have not run yet, then recreate them (used when a class schedule changes). Replacement sessions are kept. */
    public function regenerateUpcoming(ClassRoom $class): int
    {
        $class->sessions()->where('status', 'scheduled')->whereNull('replaces_session_id')->where('starts_at', '>', now())->delete();

        return $this->forClass($class, now(), now()->addWeeks(config('sessions.generate_weeks_ahead')));
    }
}
