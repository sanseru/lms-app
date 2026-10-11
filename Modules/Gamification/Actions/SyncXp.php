<?php

namespace Modules\Gamification\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Gamification\Models\XpEvent;
use Modules\Gamification\Support\XpRules;

/**
 * Sync a student's XP history with the source data: new events are added,
 * events whose source is gone (e.g. a deleted answer) are removed. Safe to repeat.
 */
class SyncXp
{
    public function __construct(private XpRules $rules) {}

    public function handle(User $student): void
    {
        DB::transaction(function () use ($student) {
            foreach ($this->rules->all() as $rule) {
                $events = $rule->events($student)->keyBy('source_id');
                $have = XpEvent::where('user_id', $student->id)->where('rule', $rule->key())->pluck('source_id')->all();

                $stale = array_diff($have, $events->keys()->all());
                if ($stale) {
                    XpEvent::where('user_id', $student->id)->where('rule', $rule->key())->whereIn('source_id', $stale)->delete();
                }

                $new = $events->except($have)->map(fn ($e) => [
                    'user_id' => $student->id,
                    'rule' => $rule->key(),
                    'source_id' => $e['source_id'],
                    'points' => $rule->points(),
                    'occurred_at' => Carbon::parse($e['at'] ?? now()),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($new->chunk(200) as $chunk) {
                    XpEvent::insertOrIgnore($chunk->values()->all());
                }
            }
        });
    }
}
