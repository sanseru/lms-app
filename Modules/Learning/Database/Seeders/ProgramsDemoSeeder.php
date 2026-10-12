<?php

namespace Modules\Learning\Database\Seeders;

use Modules\Learning\Models\Course;
use Modules\Learning\Models\Program;

/**
 * Demo programs (Python, Scratch, Web) with ordered levels, and the Python Start 1
 * items grouped into numbered lessons. Runs after the Learning and Coding demo seeders.
 */
class ProgramsDemoSeeder
{
    public function run(): void
    {
        $python = Program::create(['name' => 'Python', 'color' => 'violet', 'position' => 1, 'description' => 'From your first print() to real programs, one level at a time.']);
        $scratch = Program::create(['name' => 'Scratch', 'color' => 'amber', 'position' => 2, 'description' => 'Make games and stories by snapping colorful blocks together.']);
        $web = Program::create(['name' => 'Web Development', 'color' => 'sky', 'position' => 3, 'description' => 'Build your own web pages with HTML and CSS.']);

        $start1 = Course::where('title', 'Python Start: Programming Basics')->firstOrFail();
        $start1->update(['program_id' => $python->id, 'level_order' => 1]);
        Course::where('title', 'Scratch: Make Your First Game')->update(['program_id' => $scratch->id, 'level_order' => 1]);
        Course::where('title', 'Web Basics: HTML & CSS')->update(['program_id' => $web->id, 'level_order' => 1]);

        $this->groupStart1($start1);
        $this->start2($python, $start1);
    }

    /** Python Start 1: lessons in map order, each with its tasks. */
    private function groupStart1(Course $course): void
    {
        $plan = [
            'Getting started' => ['Warm-up Mission', 'Hello, Python!'],
            'Variables' => ['Variables and data types', 'Quiz: Variables and data types', 'Find the area of a square'],
            'Making choices' => ['If and else', 'Function: even or odd?', 'Arrange: rocket countdown'],
            'Final project' => ['Project: Number guessing game', 'Final Mission'],
        ];

        $items = $course->lessons()->get()->keyBy('title');
        $position = 1;
        foreach ($plan as $title => $tasks) {
            $unit = $course->units()->create(['title' => $title]);
            foreach ($tasks as $task) {
                $items->get($task)?->forceFill(['unit_id' => $unit->id, 'position' => $position++])->save();
            }
        }

        // Anything not in the plan keeps its order after the planned lessons.
        $course->lessons()->whereNull('unit_id')->get()->each(fn ($l) => $l->forceFill(['position' => $position++])->save());
    }

    /** Python Start 2: the next level, locked until a student finishes Start 1. */
    private function start2(Program $python, Course $start1): void
    {
        $course = Course::create([
            'program_id' => $python->id, 'level_order' => 2, 'status' => 'published', 'created_by' => $start1->created_by,
            'title' => 'Python Start 2: Loops and Lists', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet',
            'description' => 'Repeat things with loops, keep many values in lists, and build a quiz game.',
        ]);

        $lessons = [
            'Loops' => [
                ['material', 'Repeat with for', "## Doing things again and again\n\n```python\nfor i in range(3):\n    print(\"Hip hip hooray!\")\n```\n\n`range(3)` gives the numbers 0, 1 and 2, so the line runs three times."],
                ['quiz', 'Quiz: for loops', null, [['How many times does `for i in range(5):` repeat?', [['5', true], ['4', false], ['6', false]]]]],
            ],
            'Lists' => [
                ['material', 'Many values in one list', "## A list is a row of boxes\n\n```python\nfruits = [\"apple\", \"mango\", \"banana\"]\nprint(fruits[0])\n```\n\nThe first item is at position **0**."],
                ['quiz', 'Quiz: lists', null, [['What does `[\"a\", \"b\", \"c\"][1]` give?', [['\"b\"', true], ['\"a\"', false], ['\"c\"', false]]]]],
            ],
            'Quiz game project' => [
                ['assignment', 'Project: Quiz game', "Make a quiz with at least 3 questions stored in a list. Count the score and print it at the end.\n\nUpload your `.py` file or paste a link."],
            ],
        ];

        $position = 1;
        foreach ($lessons as $title => $tasks) {
            $unit = $course->units()->create(['title' => $title]);
            foreach ($tasks as $task) {
                $lesson = $course->lessons()->create([
                    'unit_id' => $unit->id, 'position' => $position++, 'type' => $task[0], 'title' => $task[1], 'body' => $task[2],
                    'duration_minutes' => $task[0] === 'assignment' ? 45 : 15, 'pass_score' => $task[0] === 'quiz' ? 70 : null,
                ]);
                foreach ($task[3] ?? [] as $i => [$prompt, $options]) {
                    $lesson->questions()->create(['position' => $i + 1, 'prompt' => $prompt, 'type' => 'single', 'points' => 1,
                        'options' => array_map(fn ($o) => ['text' => $o[0], 'correct' => $o[1]], $options)]);
                }
            }
        }
    }
}
