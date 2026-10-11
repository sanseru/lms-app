<?php

namespace Modules\Leads\Database\Seeders;

use App\Models\User;
use Modules\Leads\Models\Lead;

/** A few leads at each pipeline stage. */
class LeadsDemoSeeder
{
    public function run(): void
    {
        $teacher = User::where('username', 'sari')->first();
        $budi = User::where('username', 'budi')->first();

        $rows = [
            ['Mrs Maya', '0813-1111-2201', 'Kenzo', 9, 'Grade 3', 'Scratch Junior & Scratch', 'instagram', 'new', 2, 'Loves Minecraft, has never coded before.'],
            ['Mr Hendra', '0813-1111-2202', 'Alya', 12, 'Grade 6', 'Python Pro', 'google', 'new', 30, null],
            ['Mrs Desi', '0813-1111-2203', 'Rafa', 10, 'Grade 4', 'Game Maker', 'teman', 'contacted', 26, 'Asked for a Saturday slot.'],
            ['Mrs Tari', '0813-1111-2204', 'Nayla', 14, 'Grade 8', 'Web Developer', 'tiktok', 'trial_scheduled', 50, null],
            ['Mr Rudi', '0813-1111-2205', 'Gibran', 8, 'Grade 2', 'Scratch Junior & Scratch', 'sekolah', 'trial_scheduled', 70, null],
            ['Mrs Lina', '0813-1111-2206', 'Bima', 11, 'Grade 5', 'Python Pro', 'instagram', 'trial_done', 100, null],
            ['Mrs Wulan', '0813-1111-2207', 'Citra', 13, 'Grade 7', 'AI & Data Explorer', 'google', 'lost', 300, null],
        ];

        foreach ($rows as $i => [$parent, $phone, $child, $age, $grade, $interest, $source, $status, $hoursAgo, $message]) {
            $created = now()->subHours($hoursAgo);
            $lead = Lead::create([
                'parent_name' => $parent, 'phone' => $phone, 'child_name' => $child, 'child_age' => $age, 'child_grade' => $grade,
                'interest' => $interest, 'source' => $source, 'status' => $status, 'message' => $message,
                'preferred_time' => $i % 2 ? 'Weekdays after 4 pm' : 'Saturday morning',
                'contacted_at' => $status !== 'new' ? $created->copy()->addHours(2) : null,
                'trial_at' => in_array($status, ['trial_scheduled', 'trial_done']) ? ($status === 'trial_done' ? now()->subDays(2)->setTime(16, 0) : now()->addDays($i - 2)->setTime(16, 0)) : null,
                'trial_teacher_id' => in_array($status, ['trial_scheduled', 'trial_done']) ? ($i % 2 ? $teacher?->id : $budi?->id) : null,
                'trial_result' => $status === 'trial_done' ? 'Picked up variables and if statements quickly. Loved it when the program guessed a number. A good fit for Python Start.' : null,
                'trial_interest' => $status === 'trial_done' ? 5 : null,
                'lost_reason' => $status === 'lost' ? 'Schedule does not work' : null,
                'closed_at' => $status === 'lost' ? now()->subDays(3) : null,
            ]);
            $lead->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();
            $lead->notes()->make(['type' => 'status', 'body' => 'Signed up through the free trial form on the website.'])->forceFill(['created_at' => $created])->save();
        }
    }
}
