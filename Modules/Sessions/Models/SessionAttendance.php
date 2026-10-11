<?php

namespace Modules\Sessions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAttendance extends Model
{
    public const STATUSES = [
        'present' => 'Present',
        'excused' => 'Excused',
        'sick' => 'Sick',
        'absent' => 'Absent',
    ];

    protected $fillable = ['class_session_id', 'student_id', 'status', 'note', 'counts_quota', 'student_package_id'];

    protected function casts(): array
    {
        return ['counts_quota' => 'boolean'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
