<?php

namespace Modules\Gamification\Support;

/**
 * Badges are derived from XP history: a badge is earned when the number of
 * events for a rule reaches its target. No separate table needed.
 */
class Badges
{
    /** @var array<string, array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string}> key => [emoji, name, how to earn, rule, target, color] */
    public const ALL = [
        'langkah_pertama' => ['👣', 'First Steps', 'Finish 1 lesson', 'lesson_done', 1, 'sky'],
        'kutu_buku' => ['📚', 'Bookworm', 'Finish 10 lessons', 'lesson_done', 10, 'violet'],
        'jago_kuis' => ['🧠', 'Quiz Whiz', 'Pass 3 quizzes', 'quiz_passed', 3, 'amber'],
        'nilai_sempurna' => ['💯', 'Perfect Score', 'Score 100 on a quiz', 'quiz_perfect', 1, 'rose'],
        'coder_cilik' => ['💻', 'Little Coder', 'Pass 1 coding exercise', 'code_passed', 1, 'emerald'],
        'ninja_kode' => ['🥷', 'Code Ninja', 'Pass 5 coding exercises', 'code_passed', 5, 'slate'],
        'rajin_hadir' => ['📅', 'Always There', 'Attend 8 sessions', 'attended', 8, 'sky'],
        'super_rajin' => ['🔥', 'Super Streak', 'Attend 24 sessions', 'attended', 24, 'orange'],
        'berani_bertanya' => ['🙋', 'Brave Asker', 'Ask 1 question', 'forum_asked', 1, 'violet'],
        'penolong' => ['🤝', 'Helping Hand', 'Have your answer picked as best', 'forum_answer_accepted', 1, 'emerald'],
        'tukang_tugas' => ['📝', 'Homework Hero', 'Get 3 assignments graded', 'assignment_graded', 3, 'amber'],
        'pembangun' => ['🏗️', 'Builder', 'Finish 1 project', 'project_completed', 1, 'orange'],
        'lulusan' => ['🎓', 'Graduate', 'Earn 1 certificate', 'certificate', 1, 'rose'],
    ];
}
