<?php

namespace Modules\Gamification\Support;

/**
 * Lencana diturunkan dari riwayat XP: lencana didapat saat jumlah kejadian
 * suatu aturan mencapai target. Tidak perlu tabel tersendiri.
 */
class Badges
{
    /** @var array<string, array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string}> key => [emoji, nama, cara mendapat, aturan, target, warna] */
    public const ALL = [
        'langkah_pertama' => ['👣', 'Langkah Pertama', 'Selesaikan 1 pelajaran', 'lesson_done', 1, 'sky'],
        'kutu_buku' => ['📚', 'Kutu Buku', 'Selesaikan 10 pelajaran', 'lesson_done', 10, 'violet'],
        'jago_kuis' => ['🧠', 'Jago Kuis', 'Lulus 3 kuis', 'quiz_passed', 3, 'amber'],
        'nilai_sempurna' => ['💯', 'Nilai Sempurna', 'Dapat nilai 100 di kuis', 'quiz_perfect', 1, 'rose'],
        'coder_cilik' => ['💻', 'Coder Cilik', 'Lulus 1 soal coding', 'code_passed', 1, 'emerald'],
        'ninja_kode' => ['🥷', 'Ninja Kode', 'Lulus 5 soal coding', 'code_passed', 5, 'slate'],
        'rajin_hadir' => ['📅', 'Rajin Hadir', 'Hadir di 8 sesi', 'attended', 8, 'sky'],
        'super_rajin' => ['🔥', 'Super Rajin', 'Hadir di 24 sesi', 'attended', 24, 'orange'],
        'berani_bertanya' => ['🙋', 'Berani Bertanya', 'Ajukan 1 pertanyaan', 'forum_asked', 1, 'violet'],
        'penolong' => ['🤝', 'Penolong', 'Jawabanmu dipilih terbaik', 'forum_answer_accepted', 1, 'emerald'],
        'tukang_tugas' => ['📝', 'Tukang Tugas', 'Dapat nilai di 3 tugas', 'assignment_graded', 3, 'amber'],
        'pembangun' => ['🏗️', 'Pembangun', 'Selesaikan 1 proyek', 'project_completed', 1, 'orange'],
        'lulusan' => ['🎓', 'Lulusan', 'Dapat 1 sertifikat', 'certificate', 1, 'rose'],
    ];
}
