<?php

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\Course;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('learning.manage');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:60'],
            'level' => ['required', Rule::in(array_keys(ClassRoom::LEVELS))],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', Rule::in(ClassRoom::COLORS)],
            'status' => ['required', Rule::in(array_keys(Course::STATUSES))],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'level_order' => ['nullable', 'required_with:program_id', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'title', 'subject' => 'subject', 'level' => 'level', 'description' => 'description', 'color' => 'color', 'program_id' => 'program', 'level_order' => 'level number'];
    }
}
