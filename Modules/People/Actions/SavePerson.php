<?php

namespace Modules\People\Actions;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Enums\Role;
use Modules\People\Models\StudentProfile;
use Modules\People\Models\TeacherProfile;

/**
 * Membuat atau memperbarui akun beserta profil sesuai role.
 */
class SavePerson
{
    private const ACCOUNT_FIELDS = ['name', 'username', 'email', 'phone', 'status'];

    public function student(array $data, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $user) {
            $user = $this->saveAccount($data, Role::Student, $user);

            $profile = Arr::only($data, ['birth_date', 'gender', 'school', 'grade', 'address', 'notes', 'joined_at']);
            $user->studentProfile()->updateOrCreate([], $profile + [
                'student_code' => $user->studentProfile?->student_code ?? StudentProfile::nextCode(),
            ]);

            if (array_key_exists('guardian_ids', $data)) {
                $user->guardians()->sync(User::role(Role::Guardian->value)->whereIn('id', $data['guardian_ids'])->pluck('id'));
            }

            return $user;
        });
    }

    public function teacher(array $data, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $user) {
            $user = $this->saveAccount($data, Role::Teacher, $user);

            $user->teacherProfile()->updateOrCreate([], Arr::only($data, ['expertise', 'rate_per_session', 'bank_name', 'bank_account', 'bio', 'joined_at']) + [
                'employee_code' => $user->teacherProfile?->employee_code ?? TeacherProfile::nextCode(),
            ]);

            return $user;
        });
    }

    public function guardian(array $data, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $user) {
            $user = $this->saveAccount($data, Role::Guardian, $user);

            if (array_key_exists('student_ids', $data)) {
                $user->children()->sync(
                    collect($data['student_ids'])->mapWithKeys(fn ($id) => [$id => ['relationship' => $data['relationship']]])->all()
                );
            }

            return $user;
        });
    }

    private function saveAccount(array $data, Role $role, ?User $user): User
    {
        $attributes = Arr::only($data, self::ACCOUNT_FIELDS);

        if (! empty($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        $user ??= new User;
        $user->fill($attributes);

        if (! $user->exists) {
            $user->email_verified_at = now();
        }

        $user->save();
        $user->assignRole($role->value);

        return $user;
    }
}
