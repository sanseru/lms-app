<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A programming language or track (Python, Scratch, …) made of ordered levels (courses). */
class Program extends Model
{
    protected $fillable = ['name', 'description', 'color', 'position'];

    /** Levels in learning order: Python Start 1, Python Start 2, … */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class)->orderBy('level_order')->orderBy('id');
    }

    public function toCard(): array
    {
        return $this->only('id', 'name', 'description', 'color', 'position');
    }
}
