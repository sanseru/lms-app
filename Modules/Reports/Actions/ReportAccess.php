<?php

namespace Modules\Reports\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Enums\Role;

/**
 * Who may read whose report: admins everyone, students themselves,
 * parents their children, teachers the students in classes they teach.
 */
class ReportAccess
{
    public function canView(User $viewer, User $student): bool
    {
        return match (true) {
            $viewer->hasRole(Role::Admin->value) => true,
            $viewer->id === $student->id => true,
            $viewer->hasRole(Role::Guardian->value) => $viewer->children()->whereKey($student->id)->exists(),
            $viewer->hasRole(Role::Teacher->value) => $this->noteableClassIds($viewer, $student) !== [],
            default => false,
        };
    }

    /**
     * The student's classes the viewer may write notes for.
     *
     * @return array<int, int>
     */
    public function noteableClassIds(User $viewer, User $student): array
    {
        if (! $viewer->hasAnyRole([Role::Admin->value, Role::Teacher->value])) {
            return [];
        }

        return DB::table('class_students')
            ->join('class_rooms', 'class_rooms.id', '=', 'class_students.class_room_id')
            ->where('class_students.student_id', $student->id)
            ->when(! $viewer->hasRole(Role::Admin->value), fn ($q) => $q->where('class_rooms.teacher_id', $viewer->id))
            ->pluck('class_rooms.id')->map(fn ($id) => (int) $id)->all();
    }

    /** Students whose reports appear in the viewer's list. */
    public function students(User $viewer): Builder
    {
        $query = User::role(Role::Student->value);

        return match (true) {
            $viewer->hasRole(Role::Admin->value) => $query,
            $viewer->hasRole(Role::Teacher->value) => $query->whereIn('id', DB::table('class_students')
                ->join('class_rooms', 'class_rooms.id', '=', 'class_students.class_room_id')
                ->where('class_rooms.teacher_id', $viewer->id)->select('class_students.student_id')),
            $viewer->hasRole(Role::Guardian->value) => $query->whereIn('id', $viewer->children()->select('users.id')),
            default => $query->whereKey($viewer->id),
        };
    }
}
