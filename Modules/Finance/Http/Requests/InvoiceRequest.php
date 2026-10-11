<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finance.manage');
    }

    public function rules(): array
    {
        return [
            'target' => ['required', Rule::in(['student', 'class'])],
            'student_id' => ['required_if:target,student', 'nullable', 'integer', Rule::exists('student_profiles', 'user_id')],
            'class_room_id' => ['required_if:target,class', 'nullable', 'integer', Rule::exists('class_rooms', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'period' => ['nullable', 'date_format:Y-m'],
            'amount' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'class_room_id' => 'class',
            'title' => 'invoice title',
            'amount' => 'amount',
            'due_date' => 'due date',
        ];
    }
}
