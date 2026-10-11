<?php

namespace Modules\Sessions\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Sessions\Models\ClassSession;

/**
 * Move a session to another time, or create a replacement for a cancelled session.
 *
 * The original session is kept (status "Rescheduled" or still "Cancelled") so its weekly
 * slot is not recreated by the generator; the new session points to the original.
 */
class RescheduleSession
{
    public function handle(ClassSession $session, Carbon $startsAt, Carbon $endsAt, string $reason): ClassSession
    {
        if (! $session->canBeRescheduled()) {
            throw ValidationException::withMessages(['date' => 'This session cannot be rescheduled or already has a replacement.']);
        }

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'The replacement session time has already passed.']);
        }

        if ($clash = ClassSession::clashFor($session->teacher_id, $startsAt, $endsAt, $session->id)) {
            throw ValidationException::withMessages([
                'start_time' => "The teacher already has a {$clash->classRoom?->name} session at ".$clash->starts_at->format('H:i').'–'.$clash->ends_at->format('H:i').'.',
            ]);
        }

        if (ClassSession::where('class_room_id', $session->class_room_id)->where('starts_at', $startsAt)->exists()) {
            throw ValidationException::withMessages(['start_time' => 'This class already has a session at that time.']);
        }

        return DB::transaction(function () use ($session, $startsAt, $endsAt, $reason) {
            if ($session->status === 'scheduled') {
                $session->update(['status' => 'rescheduled', 'cancel_reason' => $reason]);
            }

            return ClassSession::create([
                'class_room_id' => $session->class_room_id,
                'replaces_session_id' => $session->id,
                'teacher_id' => $session->teacher_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'zoom_url' => $session->zoom_url,
                'status' => 'scheduled',
                'internal_note' => "Replaces the {$session->starts_at->translatedFormat('d M Y H:i')} session: {$reason}",
            ]);
        });
    }
}
