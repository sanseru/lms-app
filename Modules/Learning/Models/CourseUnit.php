<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A numbered lesson on the course map (Lesson 1, Lesson 2, …). It groups the
 * course's items (theory, quiz, assignment, coding) that students see as its tasks.
 */
class CourseUnit extends Model
{
    protected $fillable = ['course_id', 'title'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'unit_id')->orderBy('position')->orderBy('id');
    }
}
