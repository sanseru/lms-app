<?php

namespace Modules\Classes\Policies;

use App\Models\User;
use Modules\Classes\Models\ClassRoom;

class ClassRoomPolicy
{
    public function view(User $user, ClassRoom $class): bool
    {
        return $class->teacher_id === $user->id
            || $class->students()->whereKey($user->id)->exists()
            || $class->students()->whereIn('users.id', $user->children()->select('users.id'))->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('classes.manage');
    }

    public function update(User $user, ClassRoom $class): bool
    {
        return $user->can('classes.manage');
    }

    public function delete(User $user, ClassRoom $class): bool
    {
        return $user->can('classes.manage');
    }

    /** Lihat link Zoom dan daftar siswa. */
    public function viewRoster(User $user, ClassRoom $class): bool
    {
        return $class->teacher_id === $user->id;
    }
}
