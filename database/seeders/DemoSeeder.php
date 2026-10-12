<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Certificates\Database\Seeders\CertificatesDemoSeeder;
use Modules\Classes\Models\ClassRoom;
use Modules\Coding\Database\Seeders\CodingDemoSeeder;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Package;
use Modules\Finance\Models\PayrollItem;
use Modules\Finance\Models\StudentPackage;
use Modules\Finance\Models\TeacherPayout;
use Modules\Forum\Database\Seeders\ForumDemoSeeder;
use Modules\Kanban\Database\Seeders\KanbanDemoSeeder;
use Modules\Lab\Database\Seeders\LabDemoSeeder;
use Modules\Leads\Database\Seeders\LeadsDemoSeeder;
use Modules\Learning\Database\Seeders\LearningDemoSeeder;
use Modules\Learning\Database\Seeders\ProgramsDemoSeeder;
use Modules\People\Actions\SavePerson;
use Modules\Sessions\Actions\CompleteSession;
use Modules\Sessions\Actions\GenerateSessions;

/**
 * Demo data for trying out the app. Every account uses the password "password".
 */
class DemoSeeder extends Seeder
{
    public function run(SavePerson $save): void
    {
        $admin = User::firstOrCreate(['username' => 'admin'], [
            'name' => 'Admin Alprogmatics',
            'email' => 'admin@alprogmatics.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $teachers = collect([
            ['Budi Santoso', 'budi', 'Python, Data Science'],
            ['Sari Wulandari', 'sari', 'Scratch, Game Design'],
            ['Rizky Pratama', 'rizky', 'Web Development, Roblox'],
        ])->map(fn ($t) => $save->teacher([
            'name' => $t[0], 'username' => $t[1], 'email' => "{$t[1]}@alprogmatics.test", 'phone' => '0812'.random_int(10000000, 99999999),
            'status' => 'active', 'password' => 'password', 'expertise' => $t[2], 'joined_at' => now()->subMonths(8),
            'rate_per_session' => 125000, 'bank_name' => 'BCA', 'bank_account' => '86'.random_int(10000000, 99999999),
        ]));

        $kids = [
            ['Aldi Saputra', 'aldi', 'Grade 5'], ['Nadia Putri', 'nadia', 'Grade 6'], ['Raka Aditya', 'raka', 'Grade 4'],
            ['Kirana Ayu', 'kirana', 'Grade 7'], ['Farel Ramadhan', 'farel', 'Grade 5'], ['Alya Zahra', 'alya', 'Grade 8'],
            ['Dimas Arya', 'dimas', 'Grade 6'], ['Salsa Amelia', 'salsa', 'Grade 3'], ['Bima Sakti', 'bima', 'Grade 7'],
            ['Citra Lestari', 'citra', 'Grade 4'],
        ];

        $students = collect($kids)->map(fn ($k) => $save->student([
            'name' => $k[0], 'username' => $k[1], 'status' => 'active', 'password' => 'password',
            'grade' => $k[2], 'school' => 'Harapan Bangsa School', 'joined_at' => now()->subMonths(random_int(1, 10)),
        ]));

        // One parent per two children.
        $students->chunk(2)->values()->each(function ($pair, $i) use ($save) {
            $last = explode(' ', $pair->first()->name)[1] ?? 'Family';
            $save->guardian([
                'name' => ($i % 2 ? 'Mr. ' : 'Mrs. ').$last, 'username' => 'ortu'.($i + 1), 'email' => 'ortu'.($i + 1).'@example.test',
                'phone' => '0813'.random_int(10000000, 99999999), 'status' => 'active', 'password' => 'password',
                'relationship' => $i % 2 ? 'ayah' : 'ibu', 'student_ids' => $pair->pluck('id')->all(),
            ]);
        });

        $classes = collect([
            ['Python Start 1', 'Python', 'beginner', 0, [2, 4], '16:00', '17:30', 'violet'],
            ['Scratch Game Maker', 'Scratch', 'beginner', 1, [3, 6], '09:00', '10:30', 'amber'],
            ['Web Development Junior', 'Web Development', 'intermediate', 2, [1, 5], '18:30', '20:00', 'sky'],
            ['Python Pro 1', 'Python', 'intermediate', 0, [6], '13:00', '15:00', 'emerald'],
        ])->map(fn ($c) => ClassRoom::create([
            'code' => ClassRoom::nextCode($c[1]), 'name' => $c[0], 'subject' => $c[1], 'level' => $c[2],
            'teacher_id' => $teachers[$c[3]]->id, 'schedule_days' => $c[4], 'start_time' => $c[5], 'end_time' => $c[6],
            'color' => $c[7], 'status' => 'active', 'capacity' => 8, 'start_date' => now()->subMonths(2)->startOfMonth()->toDateString(),
            'zoom_url' => 'https://us06web.zoom.us/j/8'.random_int(100000000, 999999999),
            'zoom_meeting_id' => '8'.random_int(10, 99).' '.random_int(1000, 9999).' '.random_int(1000, 9999), 'zoom_passcode' => 'kode'.random_int(10, 99),
            'description' => "The {$c[0]} class runs online over Zoom. Students learn the basics, then practise right away by building small projects.",
        ]));

        $students->each(function (User $student, $i) use ($classes) {
            $classes[$i % 4]->students()->attach($student->id, ['status' => 'active', 'joined_at' => now()->subMonth()]);
            if ($i % 3 === 0) {
                $classes[($i + 1) % 4]->students()->attach($student->id, ['status' => 'active', 'joined_at' => now()->subWeeks(2)]);
            }
        });

        // Monthly tuition invoices for last month (mostly paid) and this month.
        foreach ([now()->subMonth(), now()] as $month) {
            foreach ($students as $i => $student) {
                $invoice = Invoice::create([
                    'number' => Invoice::nextNumber(), 'student_id' => $student->id, 'title' => 'Tuition '.$month->translatedFormat('F Y'),
                    'period' => $month->copy()->startOfMonth(), 'amount' => 350000, 'due_date' => $month->copy()->day(10), 'issued_by' => $admin->id,
                ]);

                $isLastMonth = $month->month !== now()->month;
                if ($isLastMonth && $i < 8 || ! $isLastMonth && $i < 3) {
                    $invoice->payments()->create([
                        'amount' => 350000, 'paid_at' => $month->copy()->day(min(8, $month->daysInMonth)), 'method' => 'transfer', 'bank_name' => 'BCA',
                        'account_name' => $student->guardians()->first()?->name, 'status' => 'verified',
                        'submitted_by' => $student->guardians()->first()?->id, 'verified_by' => $admin->id, 'verified_at' => now(),
                    ]);
                    $invoice->recalculate();
                } elseif (! $isLastMonth && $i < 5) {
                    $invoice->payments()->create([
                        'amount' => 350000, 'paid_at' => today(), 'method' => 'transfer', 'bank_name' => 'Mandiri',
                        'account_name' => $student->guardians()->first()?->name, 'status' => 'pending',
                        'submitted_by' => $student->guardians()->first()?->id,
                    ]);
                }
            }
        }

        $this->sessionsPackagesAndPayroll($admin, $students, $classes);

        (new LearningDemoSeeder)->run($admin, $classes);
        (new CodingDemoSeeder)->run();
        (new ProgramsDemoSeeder)->run();
        (new ForumDemoSeeder)->run();
        (new KanbanDemoSeeder)->run();
        (new CertificatesDemoSeeder)->run();
        (new LabDemoSeeder)->run();
        (new LeadsDemoSeeder)->run();
        Artisan::call('xp:sync');
    }

    private function sessionsPackagesAndPayroll(User $admin, $students, $classes): void
    {
        $catalog = collect([
            ['Free trial', 1, 0, 14, 'One trial session for new students.'],
            ['Regular 8 sessions', 8, 680000, 60, 'Twice a week for a month.'],
            ['Saver 16 sessions', 16, 1280000, 120, 'Save 6% compared to the regular package.'],
            ['Intensive 24 sessions', 24, 1800000, 150, null],
        ])->map(fn ($p) => Package::create(['name' => $p[0], 'sessions_count' => $p[1], 'price' => $p[2], 'valid_days' => $p[3], 'description' => $p[4]]));

        // Every student has a paid regular package, active since 4 weeks ago.
        foreach ($students as $i => $student) {
            $pkg = $catalog[$i % 3 === 0 ? 2 : 1];
            $invoice = Invoice::create([
                'number' => Invoice::nextNumber(), 'student_id' => $student->id, 'title' => "Package {$pkg->name}",
                'amount' => $pkg->price, 'due_date' => now()->subWeeks(4), 'issued_by' => $admin->id,
            ]);
            StudentPackage::create([
                'student_id' => $student->id, 'package_id' => $pkg->id, 'invoice_id' => $invoice->id, 'name' => $pkg->name,
                'sessions_total' => $pkg->sessions_count, 'price' => $pkg->price, 'valid_days' => $pkg->valid_days,
            ]);
            $invoice->payments()->create([
                'amount' => $pkg->price, 'paid_at' => now()->subWeeks(4), 'method' => 'transfer', 'bank_name' => 'BCA',
                'status' => 'verified', 'verified_by' => $admin->id, 'verified_at' => now()->subWeeks(4),
                'submitted_by' => $student->guardians()->first()?->id,
            ]);
            $invoice->recalculate();
            StudentPackage::where('invoice_id', $invoice->id)->first()->activate(now()->subWeeks(4));
        }

        // Sessions of the last 4 weeks (most already have attendance filled in) and the next
        // two weeks, as the daily sessions:generate job would have made them.
        $generator = app(GenerateSessions::class);
        $complete = app(CompleteSession::class);
        $topics = ['Introduction to variables', 'For loops', 'If-else conditions', 'Writing functions', 'Lists and loops', 'Mini project: quiz', 'Events and sprites', 'Making a catch-the-ball game', 'HTML basics', 'CSS flexbox'];

        foreach ($classes as $class) {
            $generator->forClass($class, now()->subWeeks(4), now()->addWeeks(2));

            $class->sessions()->where('ends_at', '<', now()->subDays(2))->orderBy('starts_at')->get()
                ->each(function ($session, $n) use ($class, $complete, $topics) {
                    $complete->handle($session, [
                        'topic' => $topics[($n + $class->id) % count($topics)],
                        'homework' => $n % 2 ? 'Finish the exercises in the module and send a screenshot.' : null,
                        'parent_note' => 'The kids asked lots of questions and finished the core exercises.',
                        'flagged' => $n === 3 && $class->id === 2,
                        'internal_note' => $n === 3 && $class->id === 2 ? 'Two students had a patchy Zoom connection.' : null,
                        'attendances' => $class->students->map(fn ($s, $k) => [
                            'student_id' => $s->id,
                            'status' => ($n + $k) % 9 === 0 ? 'excused' : (($n + $k) % 13 === 0 ? 'absent' : 'present'),
                        ])->all(),
                    ], $class->teacher);
                });

            // The latest class has its Zoom recording, for the Online Lesson "Recording" card.
            $class->sessions()->where('status', 'completed')->latest('starts_at')->first()
                ?->update(['recording_url' => 'https://zoom.us/rec/share/demo-'.$class->code]);
        }

        // Last month's tutor fees are already summarised and paid.
        $lastMonth = now()->subMonth();
        PayrollItem::whereBetween('session_date', [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()])
            ->get()->groupBy('teacher_id')
            ->each(function ($items, $teacherId) use ($admin, $lastMonth) {
                $payout = TeacherPayout::create([
                    'number' => TeacherPayout::nextNumber(), 'teacher_id' => $teacherId,
                    'period_start' => $lastMonth->copy()->startOfMonth(), 'period_end' => $lastMonth->copy()->endOfMonth(),
                    'sessions_count' => $items->count(), 'subtotal' => $items->sum('amount'), 'total' => $items->sum('amount'),
                    'status' => 'paid', 'paid_at' => now()->startOfMonth()->addDays(2), 'created_by' => $admin->id,
                ]);
                PayrollItem::whereIn('id', $items->pluck('id'))->update(['teacher_payout_id' => $payout->id]);
            });
    }
}
