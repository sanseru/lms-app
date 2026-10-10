<?php

namespace Modules\People\Http\Requests;

use App\Models\User;

class TeacherRequest extends PersonRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('people.teachers.manage');
    }

    protected function routeUser(): ?User
    {
        return $this->route('teacher');
    }

    public function rules(): array
    {
        return [
            ...$this->accountRules(),
            'expertise' => ['nullable', 'string', 'max:160'],
            'rate_per_session' => ['required', 'integer', 'min:0', 'max:100000000'],
            'bank_name' => ['nullable', 'string', 'max:60'],
            'bank_account' => ['nullable', 'string', 'max:40'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'joined_at' => ['nullable', 'date'],
        ];
    }
}
