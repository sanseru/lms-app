<?php

namespace Modules\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Satu baris jejak audit. Append-only: tidak bisa diubah atau dihapus lewat model. */
class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    protected $guarded = [];

    public const EVENTS = [
        'created' => 'Dibuat',
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
        'login' => 'Masuk',
        'login_failed' => 'Gagal masuk',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Jejak audit tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException('Jejak audit tidak boleh dihapus.'));
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
