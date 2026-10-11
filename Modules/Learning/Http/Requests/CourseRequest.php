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
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'judul', 'subject' => 'mata pelajaran', 'level' => 'level', 'description' => 'deskripsi', 'color' => 'warna'];
    }
}
