<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;

/** A lesson (map circle) a teacher opened early for one class. */
class ClassLessonOpen extends Model
{
    protected $fillable = ['class_room_id', 'course_id', 'node_key', 'opened_by'];
}
