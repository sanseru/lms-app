<?php

namespace Modules\People\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class GuardianRequest extends PersonRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('people.guardians.manage');
    }

    protected function routeUser(): ?User
    {
        return $this->route('guardian');
    }

    public function rules(): array
    {
        return [
            ...$this->accountRules(),
            'phone' => ['required', 'string', 'max:30'],
            'relationship' => ['required', Rule::in(['ayah', 'ibu', 'wali'])],
            'student_ids' => ['array'],
            'student_ids.*' => [Rule::exists('student_profiles', 'user_id')],
        ];
    }
}
