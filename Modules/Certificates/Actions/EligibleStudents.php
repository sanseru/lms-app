<?php

namespace Modules\Certificates\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Certificates\Models\Certificate;
use Modules\Classes\Models\ClassRoom;

/** Siswa yang sudah menyelesaikan semua pelajaran terbit di kursus kelasnya tetapi belum bersertifikat. */
class EligibleStudents
{
    /** @param  Collection<int, ClassRoom>  $classes */
    public function for(Collection $classes): Collection
    {
        return $classes->filter(fn (ClassRoom $c) => $c->course_id)->flatMap(function (ClassRoom $class) {
            $lessonIds = DB::table('lessons')->where('course_id', $class->course_id)->where('is_published', true)->pluck('id');
            if ($lessonIds->isEmpty()) {
                return [];
            }

            $done = DB::table('lesson_progress')->whereIn('lesson_id', $lessonIds)
                ->groupBy('student_id')->havingRaw('count(*) >= ?', [$lessonIds->count()])
                ->selectRaw('student_id, max(completed_at) as finished_at')->get()->keyBy('student_id');

            $issued = Certificate::valid()->where('course_id', $class->course_id)->pluck('student_id')->all();

            return $class->students()->wherePivot('status', 'active')->whereIn('users.id', $done->keys())->whereNotIn('users.id', $issued)->get()
                ->map(fn (User $s) => [
                    'student' => ['id' => $s->id, 'name' => $s->name, 'initials' => $s->initials()],
                    'class' => ['id' => $class->id, 'name' => $class->name, 'color' => $class->color],
                    'course' => ['id' => $class->course_id, 'title' => $class->course->title],
                    'lessons' => $lessonIds->count(),
                    'finished_at' => Carbon::parse($done[$s->id]->finished_at)->translatedFormat('d M Y'),
                ]);
        })->values();
    }
}
