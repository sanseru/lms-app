<?php

namespace Modules\Kanban\Database\Seeders;

use Modules\Classes\Models\ClassRoom;
use Modules\Kanban\Actions\MoveCard;
use Modules\Kanban\Actions\SyncBoards;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Project;

/** Proyek contoh dengan papan siswa di berbagai tahap. */
class KanbanDemoSeeder
{
    public function run(): void
    {
        $sync = app(SyncBoards::class);
        $move = app(MoveCard::class);

        $samples = [
            ['Scratch', '🐱', 'Game Tangkap Bola', "Buat game sederhana: sebuah keranjang menangkap bola yang jatuh dari atas.\n\nMinimal ada skor dan suara saat bola tertangkap. Bagikan link proyek Scratch-mu di bagian Hasil proyek.", 10, true,
                ['Tulis ide game dan aturannya', 'Pilih sprite keranjang dan bola', 'Buat keranjang bergerak dengan panah', 'Bola jatuh dari posisi acak', 'Tambahkan skor dan suara', 'Uji coba bersama teman']],
            ['Python', '🐍', 'Kalkulator Uang Jajan', 'Buat program Python yang menghitung sisa uang jajan setelah beberapa kali belanja.', 14, true,
                ['Tulis apa yang dilakukan programmu', 'Minta input uang jajan awal', 'Ulangi input harga belanja dengan while', 'Cetak sisa uang dan peringatan jika minus', 'Uji dengan 3 contoh']],
            ['Web Development', '🌐', 'Website Hobiku', 'Buat satu halaman website tentang hobimu dengan HTML dan CSS.', 21, false,
                ['Rancang isi halaman di kertas', 'Buat struktur HTML', 'Tambahkan gambar dengan alt', 'Hias dengan CSS', 'Publikasikan dan bagikan link']],
        ];

        foreach ($samples as [$subject, $emoji, $title, $description, $days, $review, $tasks]) {
            $class = ClassRoom::where('subject', $subject)->with('teacher')->first();
            if (! $class) {
                continue;
            }

            $project = Project::create([
                'class_room_id' => $class->id, 'created_by' => $class->teacher_id, 'title' => $title, 'description' => $description,
                'emoji' => $emoji, 'due_date' => now()->addDays($days)->toDateString(), 'requires_review' => $review,
            ]);
            foreach ($tasks as $i => $task) {
                $project->templateTasks()->create(['title' => $task, 'position' => $i + 1]);
            }
            $sync->handle($project->load('templateTasks'));

            // Setiap siswa di tahap berbeda.
            $project->boards()->with('cards', 'student', 'project.classRoom')->get()->each(function (Board $board, $n) use ($move, $class, $review) {
                $cards = $board->cards->values();
                $done = min($cards->count(), [1, 3, $cards->count(), 2, 0][$n % 5]);

                foreach ($cards->take($done) as $card) {
                    $move->handle($card, 'done', 99, $review ? $class->teacher : $board->student);
                }
                if ($cards->has($done)) {
                    $move->handle($cards[$done], $review && $n % 2 ? 'review' : 'doing', 0, $board->student);
                }
                if ($n === 0 && $cards->has($done + 1)) {
                    $cards[$done + 1]->update(['feedback' => 'Bagus! Coba tambahkan juga suara saat skor bertambah.']);
                    $move->handle($cards[$done + 1], 'doing', 1, $class->teacher);
                }
                if ($done === $cards->count()) {
                    $board->update(['result_url' => 'https://scratch.mit.edu/projects/1000000'.$board->id, 'result_note' => 'Bagian paling seru: membuat bolanya jatuh makin cepat!']);
                }
            });
        }
    }
}
