<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = ['lesson_id', 'student_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public static function markDone(int $lessonId, int $studentId): void
    {
        static::firstOrCreate(['lesson_id' => $lessonId, 'student_id' => $studentId], ['completed_at' => now()]);
    }
}
