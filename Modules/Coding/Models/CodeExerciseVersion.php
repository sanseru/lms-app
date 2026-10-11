<?php

namespace Modules\Coding\Models;

use Illuminate\Database\Eloquent\Model;

/** A copy of a coding exercise at one version. A student score points to the version they submitted against. */
class CodeExerciseVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['code_exercise_id', 'version', 'snapshot', 'created_by'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
