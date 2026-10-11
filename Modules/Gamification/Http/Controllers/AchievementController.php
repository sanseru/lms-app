<?php

namespace Modules\Gamification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Gamification\Actions\Achievements;
use Modules\Gamification\Models\XpEvent;
use Modules\Gamification\Support\Levels;

class AchievementController extends Controller
{
    public function __construct(private Achievements $achievements) {}

    /** My achievements (student) or the first child's achievements (parent). */
    public function me(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('guardian') && ($child = $user->children()->orderBy('name')->first())) {
            return to_route('gamification.show', $child);
        }

        abort_unless($user->hasRole('student'), 403);

        return $this->render($user, $request->user());
    }

    public function show(Request $request, User $student): Response
    {
        $viewer = $request->user();
        abort_unless($student->hasRole('student'), 404);
        abort_unless(
            $viewer->id === $student->id
            || $viewer->hasRole('admin')
            || $viewer->children()->whereKey($student->id)->exists()
            || $student->enrolledClasses()->where('teacher_id', $viewer->id)->exists(),
            403
        );

        return $this->render($student, $viewer);
    }

    /** Teachers and admins: student XP summary per class. */
    public function classes(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['admin', 'teacher']), 403);

        $classes = ClassRoom::query()->when(! $user->hasRole('admin'), fn ($q) => $q->where('teacher_id', $user->id))
            ->where('status', 'active')->orderBy('name')->get(['id', 'name', 'color']);
        $class = $classes->firstWhere('id', $request->integer('class')) ?? $classes->first();

        $rows = [];
        if ($class) {
            $students = $class->students()->wherePivot('status', 'active')->orderBy('name')->get();
            $rows = $students->map(function (User $s) {
                $summary = $this->achievements->summary($s);

                return [
                    'id' => $s->id, 'name' => $s->name, 'initials' => $s->initials(),
                    'level' => $summary['level'], 'xp_month' => $summary['xp_month'],
                    'badges' => collect($summary['badges'])->whereNotNull('earned_at')->pluck('emoji')->all(),
                ];
            })->sortByDesc('level.xp')->values();
        }

        return Inertia::render('Gamification::Classes', [
            'classes' => $classes,
            'current' => $class?->id,
            'rows' => $rows,
        ]);
    }

    private function render(User $student, User $viewer): Response
    {
        return Inertia::render('Gamification::Achievements', [
            'student' => ['id' => $student->id, 'name' => $student->name, 'initials' => $student->initials()],
            'self' => $viewer->id === $student->id,
            'summary' => $this->achievements->summary($student),
            'leaderboards' => config('gamification.leaderboard') ? $this->leaderboards($student) : [],
            'children' => $viewer->hasRole('guardian') ? $viewer->children()->orderBy('name')->get(['users.id', 'users.name']) : [],
        ]);
    }

    /** Top five XP this month in each of the student's classes (first names only). */
    private function leaderboards(User $student): array
    {
        return $student->enrolledClasses()->wherePivot('status', 'active')->get()->map(function (ClassRoom $class) use ($student) {
            $ids = $class->students()->wherePivot('status', 'active')->pluck('users.id');
            $points = XpEvent::whereIn('user_id', $ids)->where('occurred_at', '>=', now()->startOfMonth())
                ->groupBy('user_id')->select('user_id', DB::raw('sum(points) as xp'))->orderByDesc('xp')->get();
            $names = User::whereIn('id', $points->pluck('user_id'))->get()->keyBy('id');
            $rank = $points->search(fn ($p) => $p->user_id === $student->id);

            return [
                'class' => ['id' => $class->id, 'name' => $class->name, 'color' => $class->color],
                'top' => $points->take(5)->map(fn ($p) => [
                    'id' => $p->user_id,
                    'name' => explode(' ', $names[$p->user_id]->name)[0],
                    'initials' => $names[$p->user_id]->initials(),
                    'xp' => (int) $p->xp,
                    'level' => Levels::for((int) XpEvent::where('user_id', $p->user_id)->sum('points'))['level'],
                    'me' => $p->user_id === $student->id,
                ])->values(),
                'my_rank' => $rank === false ? null : $rank + 1,
                'total' => $ids->count(),
            ];
        })->all();
    }
}
