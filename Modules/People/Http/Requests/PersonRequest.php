<?php

namespace Modules\People\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Aturan dasar akun (nama, username, email, password) yang dipakai
 * form siswa, guru, dan orang tua.
 */
abstract class PersonRequest extends FormRequest
{
    abstract protected function routeUser(): ?User;

    protected function accountRules(bool $requireEmail = false): array
    {
        $user = $this->routeUser();

        return [
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'alpha_dash', 'min:3', 'max:40', Rule::unique('users', 'username')->ignore($user)],
            'email' => [$requireEmail ? 'required' : 'nullable', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$user ? 'nullable' : 'required', 'string', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nomor HP',
            'password' => 'kata sandi',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => $this->filled('username') ? strtolower(trim($this->input('username'))) : null,
            'email' => $this->filled('email') ? strtolower(trim($this->input('email'))) : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('username') && ! $this->filled('email')) {
                $validator->errors()->add('username', 'Isi username atau email agar akun bisa dipakai login.');
            }
        });
    }
}
