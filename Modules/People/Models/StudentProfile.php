<?php

namespace Modules\People\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = ['user_id', 'student_code', 'birth_date', 'gender', 'school', 'grade', 'address', 'notes', 'joined_at'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'joined_at' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function nextCode(): string
    {
        $last = static::query()->where('student_code', 'like', 'SIS-%')->max('student_code');
        $number = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'SIS-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }
}
