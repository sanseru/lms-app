<?php

namespace Modules\Kanban\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Langkah bawaan dari guru yang disalin ke papan setiap siswa. */
class TemplateTask extends Model
{
    protected $table = 'project_template_tasks';

    protected $fillable = ['project_id', 'title', 'description', 'position'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
