<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Models\PayrollItem;
use Modules\Sessions\Events\SessionCompleted;

/** Catat honor tutor untuk sesi selesai (sekali per sesi, tidak diubah setelah masuk slip). */
class RecordTutorFee
{
    public function handle(SessionCompleted $event): void
    {
        $session = $event->session->loadMissing(['teacher.teacherProfile', 'classRoom']);

        if (! $session->teacher) {
            return;
        }

        $item = PayrollItem::firstOrNew(['class_session_id' => $session->id]);

        if ($item->teacher_payout_id) {
            return;
        }

        $item->fill([
            'teacher_id' => $session->teacher_id,
            'session_date' => $session->starts_at->toDateString(),
            'description' => $session->classRoom->name.' · '.$session->starts_at->translatedFormat('d M H:i'),
            'amount' => $session->teacher->teacherProfile?->rate_per_session ?? 0,
        ])->save();
    }
}
