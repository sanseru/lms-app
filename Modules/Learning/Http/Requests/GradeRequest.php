<?php

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('grade', $this->route('submission'));
    }

    public function rules(): array
    {
        $max = $this->route('submission')->lesson->maxScore();

        return [
            'status' => ['required', Rule::in(['graded', 'revision'])],
            'score' => [Rule::requiredIf($this->input('status') === 'graded'), 'nullable', 'integer', 'min:0', "max:$max"],
            'feedback' => [Rule::requiredIf($this->input('status') === 'revision'), 'nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['score' => 'score', 'feedback' => 'feedback'];
    }
}
