<?php

namespace Modules\Forum\Database\Seeders;

use Modules\Classes\Models\ClassRoom;
use Modules\Forum\Actions\PostAnswer;
use Modules\Forum\Actions\ToggleVote;
use Modules\Forum\Models\Question;

/** Sample questions and answers in the demo classes. */
class ForumDemoSeeder
{
    public function run(): void
    {
        $post = app(PostAnswer::class);
        $vote = app(ToggleVote::class);

        $samples = [
            'Python' => [
                [
                    'title' => 'Why do I get an "unexpected indent" error?',
                    'body' => 'I want to print the numbers 1 to 5 but I get an **IndentationError**. My code looks just like the one in the video.',
                    'code' => "for i in range(1, 6):\nprint(i)\n    print('done')",
                    'answers' => [
                        ['staff', "Python uses the spaces at the start of a line to know which lines belong inside the loop.\n\nThe `print(i)` line needs to be indented 4 spaces, and `print('done')` should **not** be indented if you only want it printed once at the end.", "for i in range(1, 6):\n    print(i)\nprint('done')"],
                        ['student', 'That happened to me too! Try pressing Tab once in front of print(i).', null],
                    ],
                    'accept' => 0, 'votes' => 3, 'pinned' => true,
                ],
                [
                    'title' => 'What is the difference between = and ==?',
                    'body' => 'The even/odd exercise has `n % 2 == 0`. Why not just use one equals sign?',
                    'answers' => [
                        ['staff', "`=` means **store** a value in a variable, for example `age = 10`.\n\n`==` means **compare**, and the result is `True` or `False`. So `n % 2 == 0` asks: is the remainder of n divided by 2 equal to 0?", null],
                    ],
                    'accept' => null, 'votes' => 2,
                ],
                [
                    'title' => 'How do I make a random number for a guessing game?',
                    'body' => 'I want to make a game where you guess a number from 1 to 10, but the number should be different every time you play.',
                    'answers' => [], 'votes' => 1,
                ],
                [
                    'title' => 'My input() numbers will not add up',
                    'body' => 'When I type 2 and 3, I get 23 instead of 5. Why?',
                    'code' => "a = input()\nb = input()\nprint(a + b)",
                    'answers' => [
                        ['student', 'Because input() gives you text. You have to turn it into a number with int().', "a = int(input())\nb = int(input())\nprint(a + b)"],
                    ],
                    'accept' => 0, 'votes' => 0, 'private' => false,
                ],
            ],
            'Web Development' => [
                [
                    'title' => 'My picture does not show up on my HTML page',
                    'body' => 'I wrote the img tag but all I see is a broken image icon.',
                    'code' => '<img src="cat.png">',
                    'answers' => [
                        ['staff', "Check two things:\n\n1. The file name must match **exactly**, including upper and lower case letters and the extension (.png or .jpg).\n2. The image file must be in the same folder as your HTML file.\n\nDon't forget to add `alt` so there is a description if the picture fails to load.", '<img src="cat.png" alt="My cat Ginger">'],
                    ],
                    'accept' => 0, 'votes' => 2,
                ],
                [
                    'title' => 'How do I put a button in the middle of the page?',
                    'body' => 'I tried `text-align: center` but the button stays on the left.',
                    'answers' => [], 'votes' => 0, 'private' => true,
                ],
            ],
            'Scratch' => [
                [
                    'title' => 'My sprite keeps going off the screen',
                    'body' => 'When I press the right arrow, the cat keeps walking until it disappears. Which block should I use?',
                    'answers' => [
                        ['staff', 'Add the **"if on edge, bounce"** block after the move block. Or use "if x position > 220 then set x to 220" so it stops at the edge.', null],
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
