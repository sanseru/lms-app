<?php

namespace Modules\Gamification\Support;

/**
 * Badges are derived from XP history: a badge is earned when the number of
 * events for a rule reaches its target. No separate table needed.
 */
class Badges
{
    /**
     * The rule may be a list of rules whose events add up (e.g. quizzes + coding tasks).
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string|list<string>, 4: int, 5: string}> key => [emoji, name, how to earn, rule, target, color]
     */
    public const ALL = [
        // The six badges on the client prototype's home page.
        'first_project' => ['🌟', 'First project', 'Share 1 project to the Community', 'lab_shared', 1, 'amber'],
        'three_projects' => ['🚀', '3 projects', 'Share 3 projects to the Community', 'lab_shared', 3, 'sky'],
        'first_like' => ['❤️', 'First like', 'Get a like on a shared project', 'lab_liked', 1, 'rose'],
        'first_comment' => ['💬', 'First comment', "Comment on a classmate's project", 'lab_commented', 1, 'violet'],
        'python_starter' => ['🐍', 'Python starter', 'Finish 1 Python lesson', 'python_lesson', 1, 'emerald'],
        'ten_solved' => ['🧩', '10 questions solved', 'Pass 10 quizzes or coding tasks', ['quiz_passed', 'code_passed'], 10, 'orange'],

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
