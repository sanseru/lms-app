<?php

namespace Modules\People\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherProfile extends Model
{
    protected $fillable = ['user_id', 'employee_code', 'expertise', 'rate_per_session', 'bank_name', 'bank_account', 'bio', 'joined_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'date', 'rate_per_session' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function nextCode(): string
    {
        $last = static::query()->where('employee_code', 'like', 'GRU-%')->max('employee_code');
        $number = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'GRU-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
