<?php

namespace Modules\Lab\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabComment extends Model
{
    protected $fillable = ['lab_project_id', 'user_id', 'body'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(LabProject::class, 'lab_project_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
