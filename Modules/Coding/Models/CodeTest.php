<?php

namespace Modules\Coding\Models;

use Illuminate\Database\Eloquent\Model;

class CodeTest extends Model
{
    protected $fillable = ['code_exercise_id', 'position', 'kind', 'input', 'expected', 'property', 'is_hidden', 'weight', 'fail_message'];

    protected function casts(): array
    {
        return ['is_hidden' => 'boolean', 'weight' => 'integer'];
    }

    /**
     * Shape sent to the student's browser. Hidden tests only carry the input
     * (so the code can run), without the expected answer.
     */
    public function toPublic(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'input' => $this->input,
            'property' => $this->property,
            'expected' => $this->is_hidden ? null : $this->expected,
            'is_hidden' => $this->is_hidden,
            'weight' => $this->weight,
        ];
    }

    public function toEditor(): array
    {
        return $this->only('id', 'kind', 'input', 'expected', 'property', 'is_hidden', 'weight', 'fail_message');
    }
}
