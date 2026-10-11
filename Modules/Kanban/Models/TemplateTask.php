<?php

namespace Modules\Kanban\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A default step from the teacher that is copied to every student's board. */
class TemplateTask extends Model
{
    protected $table = 'project_template_tasks';

    protected $fillable = ['project_id', 'title', 'description', 'position'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
