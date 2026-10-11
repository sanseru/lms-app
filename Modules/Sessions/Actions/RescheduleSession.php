<?php

namespace Modules\Sessions\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Sessions\Models\ClassSession;

/**
 * Pindahkan sesi ke waktu lain, atau buat sesi pengganti untuk sesi yang dibatalkan.
 *
 * Sesi asal tetap tersimpan (status "Dipindah" atau tetap "Batal") supaya slot jadwal
 * mingguannya tidak dibuat ulang oleh generator; sesi baru menunjuk ke sesi asal.
 */
class RescheduleSession
{
    public function handle(ClassSession $session, Carbon $startsAt, Carbon $endsAt, string $reason): ClassSession
    {
        if (! $session->canBeRescheduled()) {
            throw ValidationException::withMessages(['date' => 'Sesi ini tidak bisa dipindah atau sudah punya sesi pengganti.']);
        }

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages(['start_time' => 'Waktu sesi pengganti sudah lewat.']);
        }

        if ($clash = ClassSession::clashFor($session->teacher_id, $startsAt, $endsAt, $session->id)) {
            throw ValidationException::withMessages([
                'start_time' => "Guru sudah ada sesi {$clash->classRoom?->name} pada ".$clash->starts_at->format('H:i').'–'.$clash->ends_at->format('H:i').'.',
            ]);
        }

        if (ClassSession::where('class_room_id', $session->class_room_id)->where('starts_at', $startsAt)->exists()) {
            throw ValidationException::withMessages(['start_time' => 'Kelas ini sudah punya sesi pada waktu tersebut.']);
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
                'internal_note' => "Pengganti sesi {$session->starts_at->translatedFormat('d M Y H:i')}: {$reason}",
            ]);
        });
    }
}
