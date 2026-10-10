<?php

namespace Modules\Sessions\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Sessions\Events\SessionCompleted;
use Modules\Sessions\Models\ClassSession;

/**
 * Simpan absensi + catatan dalam satu langkah lalu tandai sesi selesai.
 * Bisa dipanggil ulang untuk koreksi; event selalu dipicu agar kuota dan honor ikut sinkron.
 */
class CompleteSession
{
    public function handle(ClassSession $session, array $data, User $by): ClassSession
    {
        DB::transaction(function () use ($session, $data, $by) {
            $session->fill(collect($data)->only(['topic', 'homework', 'parent_note', 'internal_note', 'flagged', 'recording_url'])->all());

            if ($session->status !== 'completed') {
                $session->forceFill(['status' => 'completed', 'completed_at' => now(), 'completed_by' => $by->id]);
            }

            $session->save();

            $enrolled = $session->loadMissing('classRoom')->classRoom->students()->pluck('users.id')->all();
            $quotaStatuses = config('sessions.quota_statuses');

            foreach ($data['attendances'] as $row) {
                if (! in_array((int) $row['student_id'], $enrolled)) {
                    continue;
                }

                $session->attendances()->updateOrCreate(
                    ['student_id' => $row['student_id']],
                    ['status' => $row['status'], 'note' => $row['note'] ?? null, 'counts_quota' => in_array($row['status'], $quotaStatuses)],
                );
            }
        });

        SessionCompleted::dispatch($session->fresh());

        return $session;
    }
}
