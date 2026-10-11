<?php

namespace Modules\Kanban\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Classes\Models\ClassRoom;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project ? $this->user()->can('manage', $project) : $this->user()->can('kanban.manage');
    }

    public function rules(): array
    {
        return [
            'class_room_id' => [$this->route('project') ? 'prohibited' : 'required', 'integer', 'exists:class_rooms,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'emoji' => ['required', 'string', 'max:16'],
            'due_date' => ['nullable', 'date'],
            'requires_review' => ['boolean'],
            'status' => ['required', Rule::in(['active', 'archived'])],
            'tasks' => ['array', 'max:30'],
            'tasks.*.id' => ['nullable', 'integer'],
            'tasks.*.title' => ['required', 'string', 'max:150'],
            'tasks.*.description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['class_room_id' => 'class', 'title' => 'title', 'due_date' => 'due date', 'tasks.*.title' => 'step title'];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $class = ClassRoom::find($this->input('class_room_id'));

            if ($class && ! $this->user()->hasRole('admin') && $class->teacher_id !== $this->user()->id) {
                $validator->errors()->add('class_room_id', 'You can only create projects for classes you teach.');
            }
        }];
    }
}
