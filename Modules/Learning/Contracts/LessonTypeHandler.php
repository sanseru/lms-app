<?php

namespace Modules\Learning\Contracts;

use App\Models\User;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;

/**
 * Jenis isi kursus tambahan dari modul lain (mis. soal coding).
 * Modul mendaftarkannya lewat LessonTypes::register() di service provider-nya;
 * modul Belajar lalu menampilkan jenis itu di daftar isi, progres, dan rapor.
 */
interface LessonTypeHandler
{
    public function key(): string;

    public function label(): string;

    /** Nama ikon di komponen Icon.vue. */
    public function icon(): string;

    public function createUrl(Course $course): string;

    public function editUrl(Lesson $lesson): string;

    /** Halaman Inertia untuk membuka isi ini, mis. "Coding::Exercises/Show". */
    public function showPage(): string;

    /** Props tambahan untuk halaman show (di luar data kursus/daftar isi). */
    public function showData(Lesson $lesson, User $user, bool $manage, bool $learn): array;

    /**
     * Nilai terbaik siswa per isi, untuk rapor.
     *
     * @param  array<int>  $lessonIds
     * @return array<int, array{best: int|null, tries: int}>
     */
    public function scores(array $lessonIds, int $studentId): array;
}
