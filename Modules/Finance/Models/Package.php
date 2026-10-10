<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/** Paket kuota sesi yang dijual (mis. 8 sesi berlaku 60 hari). */
class Package extends Model
{
    protected $fillable = ['name', 'sessions_count', 'price', 'valid_days', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price' => 'integer', 'sessions_count' => 'integer', 'valid_days' => 'integer'];
    }
}
