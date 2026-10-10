<?php

namespace Modules\People\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class StudentRequest extends PersonRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('people.students.manage');
    }

    protected function routeUser(): ?User
    {
        return $this->route('student');
    }

    public function rules(): array
    {
        return [
            ...$this->accountRules(),
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'school' => ['nullable', 'string', 'max:120'],
            'grade' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'joined_at' => ['nullable', 'date'],
            'guardian_ids' => ['array'],
            'guardian_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
