<?php

namespace Modules\Leads\Database\Seeders;

use App\Models\User;
use Modules\Leads\Models\Lead;

/** Beberapa calon siswa di tiap tahap pipeline. */
class LeadsDemoSeeder
{
    public function run(): void
    {
        $teacher = User::where('username', 'sari')->first();
        $budi = User::where('username', 'budi')->first();

        $rows = [
            ['Ibu Maya', '0813-1111-2201', 'Kenzo', 9, '3 SD', 'Scratch Junior & Scratch', 'instagram', 'new', 2, 'Anak suka Minecraft, belum pernah coding.'],
            ['Bapak Hendra', '0813-1111-2202', 'Alya', 12, '6 SD', 'Python Pro', 'google', 'new', 30, null],
            ['Ibu Desi', '0813-1111-2203', 'Rafa', 10, '4 SD', 'Game Maker', 'teman', 'contacted', 26, 'Minta jadwal Sabtu.'],
            ['Ibu Tari', '0813-1111-2204', 'Nayla', 14, '8 SMP', 'Web Developer', 'tiktok', 'trial_scheduled', 50, null],
            ['Bapak Rudi', '0813-1111-2205', 'Gibran', 8, '2 SD', 'Scratch Junior & Scratch', 'sekolah', 'trial_scheduled', 70, null],
            ['Ibu Lina', '0813-1111-2206', 'Bima', 11, '5 SD', 'Python Pro', 'instagram', 'trial_done', 100, null],
            ['Ibu Wulan', '0813-1111-2207', 'Citra', 13, '7 SMP', 'AI & Data Explorer', 'google', 'lost', 300, null],
        ];

        foreach ($rows as $i => [$parent, $phone, $child, $age, $grade, $interest, $source, $status, $hoursAgo, $message]) {
            $created = now()->subHours($hoursAgo);
            $lead = Lead::create([
                'parent_name' => $parent, 'phone' => $phone, 'child_name' => $child, 'child_age' => $age, 'child_grade' => $grade,
                'interest' => $interest, 'source' => $source, 'status' => $status, 'message' => $message,
                'preferred_time' => $i % 2 ? 'Hari kerja setelah 16.00' : 'Sabtu pagi',
                'contacted_at' => $status !== 'new' ? $created->copy()->addHours(2) : null,
                'trial_at' => in_array($status, ['trial_scheduled', 'trial_done']) ? ($status === 'trial_done' ? now()->subDays(2)->setTime(16, 0) : now()->addDays($i - 2)->setTime(16, 0)) : null,
                'trial_teacher_id' => in_array($status, ['trial_scheduled', 'trial_done']) ? ($i % 2 ? $teacher?->id : $budi?->id) : null,
                'trial_result' => $status === 'trial_done' ? 'Cepat paham variabel dan if. Senang saat programnya menebak angka. Cocok masuk Python Start.' : null,
                'trial_interest' => $status === 'trial_done' ? 5 : null,
                'lost_reason' => $status === 'lost' ? 'Jadwal tidak cocok' : null,
                'closed_at' => $status === 'lost' ? now()->subDays(3) : null,
            ]);
            $lead->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();
            $lead->notes()->make(['type' => 'status', 'body' => 'Mendaftar lewat formulir coba gratis di website.'])->forceFill(['created_at' => $created])->save();
        }
    }
}
