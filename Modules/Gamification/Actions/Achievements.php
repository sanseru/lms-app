<?php

namespace Modules\Gamification\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Gamification\Models\XpEvent;
use Modules\Gamification\Support\Badges;
use Modules\Gamification\Support\Levels;
use Modules\Gamification\Support\XpRules;

/** A student's XP, level and badge summary. */
class Achievements
{
    public function __construct(private SyncXp $sync, private XpRules $rules) {}

    public function summary(User $student, bool $sync = true): array
    {
        if ($sync) {
            $this->sync->handle($student);
        }

        $events = XpEvent::where('user_id', $student->id)->orderBy('occurred_at')->get(['rule', 'source_id', 'points', 'occurred_at']);
        $total = (int) $events->sum('points');

        return [
            'level' => Levels::for($total),
            'xp_month' => (int) $events->where('occurred_at', '>=', now()->startOfMonth())->sum('points'),
            'badges' => $this->badges($events),
            // Milestone rules (0 XP) only feed badges, so they stay out of the XP lists.
            'recent' => $events->where('points', '>', 0)->sortByDesc('occurred_at')->take(8)->values()->map(fn (XpEvent $e) => [
                'label' => $this->rules->get($e->rule)?->label() ?? $e->rule,
                'points' => $e->points,
                'at' => $e->occurred_at->diffForHumans(),
            ]),
            'by_rule' => collect($this->rules->all())->filter(fn ($rule) => $rule->points() > 0)->map(fn ($rule, $key) => [
                'label' => $rule->label(),
                'points' => $rule->points(),
                'count' => $events->where('rule', $key)->count(),
            ])->values(),
        ];
    }

    /** Badges (ones not yet earned are included as locked). */
    public function badges(Collection $events): array
    {
        $byRule = $events->groupBy('rule');

        return collect(Badges::ALL)
            ->filter(fn ($b) => collect((array) $b[3])->contains(fn ($rule) => $this->rules->get($rule) !== null))
            ->map(function ($b, $key) use ($byRule) {
                [$emoji, $name, $how, $rules, $target, $color] = $b;
                $list = collect((array) $rules)->flatMap(fn ($rule) => $byRule->get($rule, collect()))->sortBy('occurred_at')->values();
                $earned = $list->count() >= $target ? $list[$target - 1]->occurred_at : null;

                return [
                    'key' => $key, 'emoji' => $emoji, 'name' => $name, 'how' => $how, 'color' => $color,
                    'progress' => min($list->count(), $target), 'target' => $target,
                    'earned_at' => $earned?->translatedFormat('d M Y'),
                    'earned_ts' => $earned?->timestamp,
                ];
            })
            ->sortBy(fn ($b) => [$b['earned_at'] ? 0 : 1, -($b['earned_ts'] ?? 0)])
            ->values()->all();
    }
}
