<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Models\StudentPackage;
use Modules\Sessions\Events\SessionCompleted;

/**
 * Hubungkan absensi yang memotong kuota ke paket aktif siswa (paket kelas ini dulu,
 * lalu paket umum, yang paling lama dibeli lebih dulu). Absensi yang tidak lagi
 * memotong kuota dilepas dari paketnya. Idempoten.
 */
class ApplySessionToQuota
{
    public function handle(SessionCompleted $event): void
    {
        $session = $event->session->loadMissing('attendances');
        $touched = collect();

        foreach ($session->attendances as $attendance) {
            if (! $attendance->counts_quota) {
                if ($attendance->student_package_id) {
                    $touched->push($attendance->student_package_id);
                    $attendance->update(['student_package_id' => null]);
                }

                continue;
            }

            if ($attendance->student_package_id) {
                continue;
            }

            $package = StudentPackage::query()
                ->where('student_id', $attendance->student_id)
                ->usableOn($session->starts_at)
                ->where(fn ($q) => $q->where('class_room_id', $session->class_room_id)->orWhereNull('class_room_id'))
                ->withCount('usages')
                ->orderByRaw('class_room_id is null')
                ->orderBy('starts_at')
                ->get()
                ->first(fn (StudentPackage $p) => $p->usages_count < $p->sessions_total);

            if ($package) {
                $attendance->update(['student_package_id' => $package->id]);
                $touched->push($package->id);
            }
        }

        StudentPackage::whereIn('id', $touched->unique())->get()->each->refreshUsage();
    }
}
