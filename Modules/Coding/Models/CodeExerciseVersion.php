<?php

namespace Modules\Coding\Models;

use Illuminate\Database\Eloquent\Model;

/** Salinan isi soal coding pada satu versi. Nilai siswa menunjuk ke versi saat ia mengumpulkan. */
class CodeExerciseVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['code_exercise_id', 'version', 'snapshot', 'created_by'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
