<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/** A session package for sale (e.g. 8 sessions valid for 60 days). */
class Package extends Model
{
    protected $fillable = ['name', 'sessions_count', 'price', 'valid_days', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price' => 'integer', 'sessions_count' => 'integer', 'valid_days' => 'integer'];
    }
}
