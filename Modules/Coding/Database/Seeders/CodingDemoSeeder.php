<?php

namespace Modules\Coding\Database\Seeders;

use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeSubmission;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;

/** Soal coding contoh (sudah lulus Uji solusi) dan beberapa jawaban siswa. */
class CodingDemoSeeder
{
    public function run(): void
    {
        $python = Course::firstWhere('subject', 'Python');
        $web = Course::firstWhere('subject', 'Web Development');

        if ($python) {
            $this->exercise($python, 'Hitung luas persegi', 'io', 'python', 15,
                "Kucing Oyen punya kebun berbentuk persegi. Bantu dia menghitung **luas kebunnya**!\n\nProgram membaca satu angka (panjang sisi) lalu mencetak luasnya.\n\n```\nInput:  4\nOutput: 16\n```",
                "sisi = int(input())\n# hitung luasnya di sini\n",
                "sisi = int(input())\nprint(sisi * sisi)\n",
                [['4', '16', false], ['0', '0', false], ['12', '144', true], ['1', '1', true]],
                ['Luas persegi = sisi × sisi', 'Di Python, perkalian memakai tanda *'], 'variabel', 1);

            $this->exercise($python, 'Fungsi: genap atau ganjil?', 'function', 'python', 15,
                'Buat fungsi `genap(n)` yang mengembalikan `True` bila `n` genap dan `False` bila ganjil.',
                "def genap(n):\n    # kembalikan True atau False\n    pass\n",
                "def genap(n):\n    return n % 2 == 0\n",
                [['[4]', 'true', false], ['[7]', 'false', false], ['[0]', 'true', true], ['[-3]', 'false', true]],
                ['Operator % memberi sisa bagi', 'Bilangan genap: sisa bagi 2 sama dengan 0'], 'percabangan', 2, 'genap');

            $this->exercise($python, 'Susun: hitung mundur roket', 'parsons', 'python', 10,
                'Susun baris kode agar program menghitung mundur dari 3 lalu mencetak **Meluncur!** 🚀',
                null,
                "for i in range(3, 0, -1):\n    print(i)\nprint(\"Meluncur!\")",
                [], ['Perulangan for ditulis paling atas', 'Baris yang menjorok masuk ke dalam perulangan'], 'perulangan', 1);
        }

        if ($web) {
            $this->exercise($web, 'Kartu profil HTML', 'html', 'html', 20,
                "Buat halaman profil sederhana:\n\n- Judul `<h1>` berisi namamu\n- Paragraf tentang hobimu\n- Gambar dengan atribut `alt`\n- Latar halaman berwarna biru muda (`lightblue`)",
                "<!doctype html>\n<html>\n  <head>\n    <style>\n      body {  }\n    </style>\n  </head>\n  <body>\n\n  </body>\n</html>\n",
                "<!doctype html>\n<html>\n  <head>\n    <style>\n      body { background-color: lightblue; }\n    </style>\n  </head>\n  <body>\n    <h1>Halo, aku Raka</h1>\n    <p>Hobiku bermain sepak bola dan membuat game.</p>\n    <img src=\"https://placehold.co/120\" alt=\"Foto Raka\">\n  </body>\n</html>\n",
                [['h1', null, false, 'exists'], ['p', null, false, 'exists'], ['img', '', false, 'attr', 'alt'], ['body', 'lightblue', false, 'style', 'background-color']],
                ['Atribut alt ditulis di dalam tag img', 'Warna latar memakai properti background-color'], 'html dasar', 1);
        }

        $this->sampleSubmissions();
    }

    private function exercise(Course $course, string $title, string $mode, string $language, int $minutes, string $body, ?string $starter, string $solution, array $tests, array $hints, string $concept, int $level, ?string $function = null): void
    {
        $lesson = $course->lessons()->create([
            'title' => $title, 'type' => 'code', 'body' => $body, 'duration_minutes' => $minutes, 'position' => $course->nextPosition(), 'is_published' => true,
        ]);

        $exercise = CodeExercise::create([
            'lesson_id' => $lesson->id, 'language' => $language, 'mode' => $mode, 'starter_code' => $starter, 'solution_code' => $solution,
            'function_name' => $function, 'hints' => $hints, 'time_limit_ms' => 2000, 'show_solution_after' => 3, 'concept' => $concept, 'level' => $level,
        ]);

        foreach ($tests as $i => $t) {
            $exercise->tests()->create([
                'position' => $i + 1, 'kind' => $t[3] ?? ($mode === 'function' ? 'function' : 'io'), 'input' => $t[0], 'expected' => $t[1],
                'is_hidden' => $t[2], 'property' => $t[4] ?? null, 'weight' => 1,
            ]);
        }

        // Contoh data: solusi sudah diuji.
        $exercise->update(['verified_hash' => $exercise->contentHash(), 'verified_at' => now()]);
    }

    private function sampleSubmissions(): void
    {
        $lessons = Lesson::where('type', 'code')->with('course.classes.students')->get();

        foreach ($lessons as $lesson) {
            $exercise = CodeExercise::with('tests')->where('lesson_id', $lesson->id)->first();
            $students = $lesson->course->classes->flatMap->students->unique('id')->values();

            foreach ($students->take(3) as $i => $student) {
                $passed = $i !== 1;
                $total = max(1, $exercise->tests->count() ?: count($exercise->parsonsLines()));
                $ok = $passed ? $total : max(0, $total - 2);
                CodeSubmission::create([
                    'lesson_id' => $lesson->id, 'student_id' => $student->id,
                    'code' => $passed ? $exercise->solution_code : ($exercise->starter_code ?? $exercise->solution_code),
                    'status' => $passed ? 'passed' : 'failed', 'score' => (int) round($ok / $total * 100),
                    'passed_tests' => $ok, 'total_tests' => $total, 'results' => [], 'graded_by' => 'browser',
                ]);
                if ($passed) {
                    LessonProgress::markDone($lesson->id, $student->id);
                }
            }
        }
    }
}
