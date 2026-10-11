<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Sessions\Models\ClassSession;

/** Tutor fee for one completed teaching session. */
class PayrollItem extends Model
{
    protected $fillable = ['teacher_id', 'class_session_id', 'teacher_payout_id', 'session_date', 'description', 'amount'];

    protected function casts(): array
    {
        return ['session_date' => 'date', 'amount' => 'integer'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(TeacherPayout::class, 'teacher_payout_id');
    }
}
