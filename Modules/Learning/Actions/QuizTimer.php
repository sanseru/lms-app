<?php

namespace Modules\Learning\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Learning\Models\Lesson;

/**
 * Waktu mulai kuis berbatas waktu disimpan di server (bukan di browser),
 * jadi memuat ulang halaman atau pindah perangkat tidak mengulang hitungan mundur.
 */
class QuizTimer
{
    /** Toleransi jaringan sebelum kiriman dianggap terlambat. */
    public const GRACE_SECONDS = 30;

    public function start(Lesson $lesson, int $studentId): CarbonImmutable
    {
        $started = $this->startedAt($lesson, $studentId);

        if (! $started) {
            $started = CarbonImmutable::now();
            Cache::put($this->key($lesson, $studentId), $started->getTimestamp(), now()->addMinutes($lesson->time_limit_minutes + 60));
        }

        return $started;
    }

    public function startedAt(Lesson $lesson, int $studentId): ?CarbonImmutable
    {
        $ts = Cache::get($this->key($lesson, $studentId));

        return $ts ? CarbonImmutable::createFromTimestamp($ts) : null;
    }

    /** Sisa detik (bisa negatif kalau waktu sudah habis), null kalau belum mulai. */
    public function remaining(Lesson $lesson, int $studentId): ?int
    {
        $started = $this->startedAt($lesson, $studentId);

        return $started ? $lesson->time_limit_minutes * 60 - (int) $started->diffInSeconds(now()) : null;
    }

    public function forget(Lesson $lesson, int $studentId): void
    {
        Cache::forget($this->key($lesson, $studentId));
    }

    private function key(Lesson $lesson, int $studentId): string
    {
        return "quiz-timer:{$lesson->id}:{$studentId}";
    }
}
