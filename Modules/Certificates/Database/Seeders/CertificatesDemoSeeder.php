<?php

namespace Modules\Certificates\Database\Seeders;

use Modules\Certificates\Actions\EligibleStudents;
use Modules\Certificates\Actions\IssueCertificate;
use Modules\Classes\Models\ClassRoom;

/** Certificates for students who finished a course, plus one contest certificate. */
class CertificatesDemoSeeder
{
    public function run(): void
    {
        $issue = app(IssueCertificate::class);
        $classes = ClassRoom::with(['course', 'teacher'])->get();

        app(EligibleStudents::class)->for($classes)->take(2)->each(function ($row) use ($issue, $classes) {
            $class = $classes->firstWhere('id', $row['class']['id']);
            $issue->handle($class->students()->findOrFail($row['student']['id']), ['class_room_id' => $class->id, 'course_id' => $class->course_id], $class->teacher);
        });

        $scratch = $classes->firstWhere('subject', 'Scratch');
        if ($student = $scratch?->students()->orderBy('name')->first()) {
            $issue->handle($student, [
                'class_room_id' => $scratch->id,
                'title' => 'Inter-Class Scratch Game Contest',
                'achievement' => 'for winning 1st place with "Ball Catcher Cat" in the',
                'issued_at' => now()->subDays(5)->toDateString(),
            ], $scratch->teacher);
        }
    }
}
