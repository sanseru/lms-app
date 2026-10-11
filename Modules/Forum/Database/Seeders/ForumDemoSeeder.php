<?php

namespace Modules\Forum\Database\Seeders;

use Modules\Classes\Models\ClassRoom;
use Modules\Forum\Actions\PostAnswer;
use Modules\Forum\Actions\ToggleVote;
use Modules\Forum\Models\Question;

/** Contoh pertanyaan dan jawaban di kelas demo. */
class ForumDemoSeeder
{
    public function run(): void
    {
        $post = app(PostAnswer::class);
        $vote = app(ToggleVote::class);

        $samples = [
            'Python' => [
                [
                    'title' => 'Kenapa muncul error "unexpected indent"?',
                    'body' => 'Aku mau mencetak angka 1 sampai 5 tapi muncul **IndentationError**. Padahal kodenya sudah sama seperti di video.',
                    'code' => "for i in range(1, 6):\nprint(i)\n    print('selesai')",
                    'answers' => [
                        ['staff', "Python memakai spasi di awal baris untuk tahu baris mana yang masuk ke dalam perulangan.\n\nBaris `print(i)` harus menjorok 4 spasi, sedangkan `print('selesai')` sebaiknya **tidak** menjorok kalau mau dicetak sekali saja di akhir.", "for i in range(1, 6):\n    print(i)\nprint('selesai')"],
                        ['student', 'Aku juga pernah begitu! Coba tekan Tab sekali di depan print(i).', null],
                    ],
                    'accept' => 0, 'votes' => 3, 'pinned' => true,
                ],
                [
                    'title' => 'Bedanya = dan == apa ya?',
                    'body' => 'Di soal genap ganjil ada `n % 2 == 0`. Kenapa tidak pakai satu tanda sama dengan saja?',
                    'answers' => [
                        ['staff', "`=` artinya **menyimpan** nilai ke variabel, misalnya `umur = 10`.\n\n`==` artinya **membandingkan**, hasilnya `True` atau `False`. Jadi `n % 2 == 0` bertanya: apakah sisa bagi n dengan 2 sama dengan 0?", null],
                    ],
                    'accept' => null, 'votes' => 2,
                ],
                [
                    'title' => 'Bagaimana cara membuat angka acak untuk game tebak angka?',
                    'body' => 'Aku mau bikin game tebak angka 1 sampai 10, tapi angkanya harus beda-beda setiap main.',
                    'answers' => [], 'votes' => 1,
                ],
                [
                    'title' => 'Program input() tidak mau dijumlah',
                    'body' => 'Kalau aku masukkan 2 dan 3, hasilnya malah 23 bukan 5. Kenapa?',
                    'code' => "a = input()\nb = input()\nprint(a + b)",
                    'answers' => [
                        ['student', 'Karena input() hasilnya teks. Harus diubah jadi angka pakai int().', "a = int(input())\nb = int(input())\nprint(a + b)"],
                    ],
                    'accept' => 0, 'votes' => 0, 'private' => false,
                ],
            ],
            'Web Development' => [
                [
                    'title' => 'Gambarku tidak muncul di halaman HTML',
                    'body' => 'Aku sudah menulis tag img tapi yang muncul cuma ikon gambar rusak.',
                    'code' => '<img src="kucing.png">',
                    'answers' => [
                        ['staff', "Cek dua hal:\n\n1. Nama file harus **persis sama**, termasuk huruf besar kecil dan ekstensinya (.png atau .jpg).\n2. File gambar harus ada di folder yang sama dengan file HTML-mu.\n\nJangan lupa tambahkan `alt` supaya ada keterangan kalau gambarnya gagal dimuat.", '<img src="kucing.png" alt="Kucingku Oyen">'],
                    ],
                    'accept' => 0, 'votes' => 2,
                ],
                [
                    'title' => 'Cara membuat tombol di tengah halaman?',
                    'body' => 'Sudah coba `text-align: center` tapi tombolnya tetap di kiri.',
                    'answers' => [], 'votes' => 0, 'private' => true,
                ],
            ],
            'Scratch' => [
                [
                    'title' => 'Sprite-ku keluar dari layar terus',
                    'body' => 'Waktu aku tekan panah kanan, kucingnya jalan terus sampai hilang. Blok apa yang harus dipakai?',
                    'answers' => [
                        ['staff', 'Tambahkan blok **"jika di tepi, pantulkan"** setelah blok gerak. Atau pakai blok "jika posisi x > 220 maka ubah x ke 220" supaya berhenti di pinggir.', null],
                    ],
                    'accept' => null, 'votes' => 1,
                ],
            ],
        ];

        foreach ($samples as $subject => $questions) {
            $class = ClassRoom::where('subject', $subject)->with(['students', 'teacher', 'course.lessons'])->first();
            if (! $class || $class->students->count() < 2) {
                continue;
            }

            foreach ($questions as $i => $q) {
                $students = $class->students->values();
                $author = $students[$i % $students->count()];
                $lesson = $class->course?->lessons->where('is_published', true)->values()->get($i + 1);

                $question = Question::create([
                    'class_room_id' => $class->id,
                    'lesson_id' => $lesson?->id,
                    'author_id' => $author->id,
                    'title' => $q['title'],
                    'body' => $q['body'],
                    'code' => $q['code'] ?? null,
                    'visibility' => ($q['private'] ?? false) ? 'private' : 'class',
                    'anonymous' => $i === 2,
                    'pinned' => $q['pinned'] ?? false,
                    'last_activity_at' => now()->subHours(30 - $i * 5),
                ]);
                $question->forceFill(['created_at' => now()->subDays(3 - min($i, 2))->subHours($i)])->saveQuietly();

                $answers = collect($q['answers'])->map(function ($a, $k) use ($post, $question, $class, $students, $author) {
                    $by = $a[0] === 'staff' ? $class->teacher : $students->first(fn ($s) => $s->id !== $author->id);

                    return $post->handle($question, $by, ['body' => $a[1], 'code' => $a[2]]);
                });

                if (($q['accept'] ?? null) !== null && $answers->has($q['accept'])) {
                    $question->update(['accepted_answer_id' => $answers[$q['accept']]->id, 'resolved_at' => now()->subHours(6)]);
                }

                $students->reject(fn ($s) => $s->id === $author->id)->take($q['votes'])
                    ->each(fn ($s) => $vote->handle($question, $s));
                $answers->each(fn ($a) => $students->reject(fn ($s) => $s->id === $a->author_id)->take(2)->each(fn ($s) => $vote->handle($a, $s)));
            }
        }
    }
}
