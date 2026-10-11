<?php

namespace Modules\Learning\Database\Seeders;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Actions\GradeQuiz;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;

/** Kursus contoh beserta progres siswa, dipanggil dari DemoSeeder. */
class LearningDemoSeeder
{
    /** @param  Collection<int, ClassRoom>  $classes */
    public function run(User $author, Collection $classes): void
    {
        $python = $this->course($author, 'Python Start: Dasar Pemrograman', 'Python', 'beginner', 'violet',
            'Mengenal Python dari nol: variabel, input, percabangan, dan perulangan, lalu membuat game tebak angka.', [
                ['material', 'Halo, Python!', 15, "## Apa itu Python?\n\nPython adalah bahasa pemrograman yang mudah dibaca, dipakai untuk game, website, sampai kecerdasan buatan.\n\nProgram pertama kita:\n\n```python\nprint(\"Halo, dunia!\")\n```\n\n> Tips: perintah `print` menampilkan tulisan ke layar.\n\n### Coba sendiri\n- Ganti tulisannya dengan namamu\n- Tambahkan baris `print` kedua", 'https://www.youtube.com/watch?v=kqtD5dpn9C8'],
                ['material', 'Variabel dan tipe data', 20, "## Variabel itu seperti kotak\n\nVariabel menyimpan nilai supaya bisa dipakai lagi.\n\n```python\nnama = \"Aldi\"\numur = 10\ntinggi = 1.4\n```\n\n| Tipe | Contoh |\n|---|---|\n| `str` (teks) | `\"halo\"` |\n| `int` (bilangan bulat) | `10` |\n| `float` (desimal) | `1.4` |\n| `bool` | `True` / `False` |"],
                ['quiz', 'Kuis: Variabel dan tipe data', 10, 'Jawab semua soal. Nilai lulus 70, boleh mencoba lagi.', null, [
                    ['Perintah untuk menampilkan tulisan ke layar adalah…', 'single', [['print()', true], ['input()', false], ['show()', false], ['write()', false]], 'print() menampilkan teks atau nilai ke layar.'],
                    ['Tipe data dari nilai 3.14 adalah…', 'single', [['int', false], ['float', true], ['str', false], ['bool', false]], 'Bilangan dengan koma desimal bertipe float.'],
                    ['Mana saja nama variabel yang benar di Python?', 'multiple', [['skor_pemain', true], ['2nilai', false], ['namaKu', true], ['nilai akhir', false]], 'Nama variabel tidak boleh diawali angka dan tidak boleh ada spasi.'],
                    ['Hasil dari `print(3 + 4 * 2)` adalah…', 'single', [['14', false], ['11', true], ['10', false]], 'Perkalian dikerjakan lebih dulu: 4 × 2 = 8, lalu 3 + 8 = 11.'],
                ]],
                ['material', 'Percabangan if dan else', 20, "## Program yang bisa memilih\n\n```python\nnilai = 80\nif nilai >= 75:\n    print(\"Lulus!\")\nelse:\n    print(\"Ayo belajar lagi\")\n```\n\nPerhatikan **indentasi** (spasi di depan baris). Python memakainya untuk tahu baris mana yang masuk ke dalam `if`."],
                ['assignment', 'Proyek: Game tebak angka', 45, "Buat game tebak angka 1 sampai 10.\n\n1. Komputer memilih angka acak dengan `random.randint(1, 10)`\n2. Pemain menebak sampai benar\n3. Beri petunjuk *terlalu besar* atau *terlalu kecil*\n\nUnggah berkas `.py` kamu, atau tempel link karyamu kalau disimpan online."],
            ]);

        $scratch = $this->course($author, 'Scratch: Bikin Game Pertamamu', 'Scratch', 'beginner', 'amber',
            'Belajar logika lewat balok warna-warni dan membuat game kejar-kejaran sendiri.', [
                ['material', 'Kenalan dengan Scratch', 15, "## Panggung, sprite, dan balok\n\n- **Panggung**: tempat semua terjadi\n- **Sprite**: karakter yang bisa bergerak\n- **Balok kode**: perintah yang disusun seperti lego\n\nBuka [scratch.mit.edu](https://scratch.mit.edu) lalu klik *Create*."],
                ['material', 'Menggerakkan karakter', 20, "## Gerak dengan tombol panah\n\nGunakan balok **when [right arrow] key pressed** lalu **change x by 10**.\n\nCoba buat karakter bisa bergerak ke 4 arah."],
                ['quiz', 'Kuis: Dasar Scratch', 8, null, null, [
                    ['Karakter yang bisa bergerak di Scratch disebut…', 'single', [['Sprite', true], ['Stage', false], ['Block', false]], 'Sprite adalah karakter atau objek di panggung.'],
                    ['Balok untuk menggerakkan sprite ke kanan adalah…', 'single', [['change x by 10', true], ['change y by 10', false], ['turn 15 degrees', false]], 'Sumbu x adalah arah kiri-kanan.'],
                    ['Balok mana yang termasuk kategori Events?', 'multiple', [['when green flag clicked', true], ['when key pressed', true], ['move 10 steps', false]], 'Events adalah pemicu awal sebuah skrip.'],
                ]],
                ['assignment', 'Proyek: Game kejar-kejaran', 60, "Buat game di mana kucing mengejar bola.\n\n- Kucing digerakkan dengan tombol panah\n- Bola pindah ke tempat acak saat tersentuh\n- Tambahkan skor\n\nKumpulkan link proyek Scratch kamu (pastikan sudah di-*Share*)."],
            ]);

        $web = $this->course($author, 'Web Dasar: HTML & CSS', 'Web Development', 'intermediate', 'sky',
            'Membuat halaman web pribadi pertama dengan HTML dan mempercantiknya dengan CSS.', [
                ['material', 'Struktur halaman HTML', 20, "## Kerangka HTML\n\n```html\n<!doctype html>\n<html>\n  <body>\n    <h1>Halo, ini webku</h1>\n    <p>Aku suka ngoding.</p>\n  </body>\n</html>\n```"],
                ['quiz', 'Kuis: Tag HTML', 8, null, null, [
                    ['Tag untuk judul paling besar adalah…', 'single', [['<h1>', true], ['<title>', false], ['<head>', false]], '<h1> adalah heading level 1.'],
                    ['Tag untuk paragraf adalah…', 'single', [['<p>', true], ['<para>', false], ['<text>', false]], null],
                ]],
                ['assignment', 'Proyek: Halaman profil', 60, 'Buat halaman profil berisi nama, hobi, dan foto. Kumpulkan link CodePen atau unggah berkas `.html`.'],
            ]);

        // Pasang kursus ke kelas (Python Pro memakai kursus Python juga sebagai contoh).
        $map = ['Python Start 1' => $python, 'Scratch Game Maker' => $scratch, 'Web Development Junior' => $web];
        foreach ($classes as $class) {
            if (isset($map[$class->name])) {
                $class->forceFill(['course_id' => $map[$class->name]->id])->saveQuietly();
            }
        }

        $this->simulateProgress($classes->filter(fn ($c) => isset($map[$c->name])), $map);
        $this->missions($python, $classes->firstWhere('name', 'Python Start 1'));
    }

    /** Misi Pemanasan (awal) dan Misi Akhir untuk kursus Python, dengan konsep per soal. */
    private function missions(Course $course, ClassRoom $class): void
    {
        $make = function (string $role, string $title, array $questions, int $position) use ($course) {
            $lesson = $course->lessons()->create([
                'title' => $title, 'type' => 'quiz', 'assessment' => $role, 'position' => $position, 'pass_score' => 0, 'duration_minutes' => 10,
                'body' => $role === 'pre' ? 'Misi singkat untuk melihat titik awalmu. Tidak memengaruhi nilai.' : 'Misi penutup untuk melihat seberapa jauh kemajuanmu.',
            ]);
            foreach ($questions as $i => [$prompt, $concept, $options]) {
                $lesson->questions()->create(['position' => $i + 1, 'prompt' => $prompt, 'type' => 'single', 'points' => 1, 'concept' => $concept,
                    'options' => array_map(fn ($o) => ['text' => $o[0], 'correct' => $o[1]], $options)]);
            }

            return $lesson->load('questions');
        };

        // Misi pemanasan di posisi paling awal.
        $course->lessons()->increment('position');
        $pre = $make('pre', 'Misi Pemanasan', [
            ['Apa output dari `print(2 + 3)`?', 'variabel', [['5', true], ['23', false], ['2 + 3', false]]],
            ['Untuk menyimpan nama pemain, kita memakai…', 'variabel', [['variabel', true], ['perulangan', false], ['komentar', false]]],
            ['`if nilai > 70:` artinya…', 'percabangan', [['jika nilai lebih dari 70', true], ['ulangi 70 kali', false], ['nilai sama dengan 70', false]]],
            ['Berapa kali `for i in range(3):` berulang?', 'perulangan', [['3', true], ['2', false], ['4', false]]],
        ], 1);
        $post = $make('post', 'Misi Akhir', [
            ['Apa output dari `print(4 * 2)`?', 'variabel', [['8', true], ['42', false], ['4 * 2', false]]],
            ['Nilai `True` dan `False` bertipe…', 'variabel', [['bool', true], ['int', false], ['str', false]]],
            ['Kapan blok `else` dijalankan?', 'percabangan', [['saat kondisi if salah', true], ['selalu', false], ['saat kondisi if benar', false]]],
            ['Berapa kali `for i in range(1, 5):` berulang?', 'perulangan', [['4', true], ['5', false], ['1', false]]],
        ], $course->nextPosition());

        $grade = app(GradeQuiz::class);
        foreach ($class->students()->get() as $i => $student) {
            // Pemanasan: hanya soal pertama benar (dan soal kedua untuk sebagian siswa).
            $grade->handle($pre, $student, $pre->questions->mapWithKeys(fn ($q, $j) => [$q->id => $j === 0 || ($j === 1 && $i % 2 === 0) ? 0 : 1])->all());
            // Misi akhir: sebagian besar benar.
            $grade->handle($post, $student, $post->questions->mapWithKeys(fn ($q, $j) => [$q->id => $j === 3 && $i % 2 ? 1 : 0])->all());
        }
    }

    private function course(User $author, string $title, string $subject, string $level, string $color, string $description, array $lessons): Course
    {
        $course = Course::create(compact('title', 'subject', 'level', 'color', 'description') + ['status' => 'published', 'created_by' => $author->id]);

        foreach ($lessons as $i => $l) {
            $lesson = $course->lessons()->create([
                'position' => $i + 1, 'type' => $l[0], 'title' => $l[1], 'duration_minutes' => $l[2], 'body' => $l[3], 'video_url' => $l[4] ?? null,
                'pass_score' => $l[0] === 'quiz' ? 70 : null, 'max_score' => $l[0] === 'assignment' ? 100 : null,
            ]);

            foreach ($l[5] ?? [] as $q => [$prompt, $type, $options, $explanation]) {
                $lesson->questions()->create([
                    'position' => $q + 1, 'prompt' => $prompt, 'type' => $type, 'points' => 1, 'explanation' => $explanation,
                    'options' => array_map(fn ($o) => ['text' => $o[0], 'correct' => $o[1]], $options),
                ]);
            }
        }

        return $course;
    }

    /** Siswa sudah menyelesaikan sebagian materi; ada tugas dinilai dan yang menunggu. */
    private function simulateProgress(Collection $classes, array $map): void
    {
        $grade = app(GradeQuiz::class);

        foreach ($classes as $class) {
            $lessons = $map[$class->name]->lessons()->with('questions')->get();

            foreach ($class->students()->get() as $s => $student) {
                $reach = min($lessons->count(), 2 + ($student->id % 3)); // berapa jauh siswa ini sudah belajar

                foreach ($lessons->take($reach) as $lesson) {
                    match ($lesson->type) {
                        'material' => LessonProgress::markDone($lesson->id, $student->id),
                        'quiz' => $this->answerQuiz($grade, $lesson, $student),
                        'assignment' => $this->submit($lesson, $student, $class, $s),
                    };
                }
            }
        }
    }

    private function answerQuiz(GradeQuiz $grade, Lesson $lesson, User $student): void
    {
        // Percobaan pertama ada salah satu soal, percobaan kedua (untuk sebagian siswa) benar semua.
        $answers = fn (bool $perfect) => $lesson->questions->mapWithKeys(fn ($q, $i) => [
            $q->id => $perfect || $i > 0 ? ($q->type === 'multiple' ? $q->correctIndexes() : $q->correctIndexes()[0]) : ($q->type === 'multiple' ? [0] : count($q->options) - 1),
        ])->all();

        $grade->handle($lesson, $student, $answers(false));
        if ($student->id % 2 === 0) {
            $grade->handle($lesson, $student, $answers(true));
        }
    }

    private function submit(Lesson $lesson, User $student, ClassRoom $class, int $index): void
    {
        $graded = $index % 2 === 0;
        $lesson->submissions()->create([
            'student_id' => $student->id,
            'body' => 'Sudah selesai! Aku menambahkan skor dan suara saat menang.',
            // Data demo tanpa tautan luar: link palsu akan mengarah ke halaman yang tidak ada.
            'link_url' => null,
            'status' => $graded ? 'graded' : 'submitted',
            'score' => $graded ? 80 + ($student->id % 4) * 5 : null,
            'feedback' => $graded ? 'Keren! Coba tambahkan batas jumlah tebakan supaya lebih menantang.' : null,
            'graded_by' => $graded ? $class->teacher_id : null,
            'graded_at' => $graded ? now()->subDays(2) : null,
            'submitted_at' => now()->subDays(3 + $index),
        ]);
        LessonProgress::markDone($lesson->id, $student->id);
    }
}
