<?php

namespace Modules\Certificates\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Certificates\Models\Certificate;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\Course;

class IssueCertificate
{
    public function handle(User $student, array $data, User $issuer): Certificate
    {
        return DB::transaction(function () use ($student, $data, $issuer) {
            $class = isset($data['class_room_id']) ? ClassRoom::with('teacher')->find($data['class_room_id']) : null;
            $course = isset($data['course_id']) ? Course::find($data['course_id']) : null;

            return Certificate::create([
                'number' => Certificate::nextNumber(),
                'code' => Certificate::newCode(),
                'student_id' => $student->id,
                'course_id' => $course?->id,
                'class_room_id' => $class?->id,
                'title' => $data['title'] ?? $course?->title ?? $class?->name,
                'achievement' => $data['achievement'] ?? 'telah menyelesaikan seluruh materi dan proyek kursus',
                'issued_at' => $data['issued_at'] ?? today(),
                'issued_by' => $issuer->id,
                'signer_name' => $class?->teacher?->name ?? $issuer->name,
            ]);
        });
    }
}
