<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Laporan siswa bahwa ada yang salah di sebuah pelajaran atau soal. */
class LessonReport extends Model
{
    public const REASONS = [
        'wrong_answer' => 'The answer or a test seems wrong',
        'unclear' => 'The instructions are unclear',
        'broken' => 'Something is broken or has a typo',
        'other' => 'Something else',
    ];

    public const STATUSES = ['open' => 'Open', 'resolved' => 'Fixed', 'dismissed' => 'No change needed'];

    protected $fillable = ['lesson_id', 'user_id', 'reason', 'message', 'status', 'response', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
