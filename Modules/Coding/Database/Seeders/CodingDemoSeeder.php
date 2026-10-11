<?php

namespace Modules\Coding\Database\Seeders;

use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeSubmission;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;

/** Sample coding exercises (already passed Test solution) and a few student answers. */
class CodingDemoSeeder
{
    public function run(): void
    {
        $python = Course::firstWhere('subject', 'Python');
        $web = Course::firstWhere('subject', 'Web Development');

        if ($python) {
            $this->exercise($python, 'Find the area of a square', 'io', 'python', 15,
                "Ginger the cat has a square garden. Help her work out **the area of her garden**!\n\nThe program reads one number (the side length) and prints the area.\n\n```\nInput:  4\nOutput: 16\n```",
                "side = int(input())\n# work out the area here\n",
                "side = int(input())\nprint(side * side)\n",
                [['4', '16', false], ['0', '0', false], ['12', '144', true], ['1', '1', true]],
                ['Area of a square = side × side', 'In Python, multiplication uses the * sign'], 'variables', 1);

            $this->exercise($python, 'Function: even or odd?', 'function', 'python', 15,
                'Write a function `is_even(n)` that returns `True` when `n` is even and `False` when it is odd.',
                "def is_even(n):\n    # return True or False\n    pass\n",
                "def is_even(n):\n    return n % 2 == 0\n",
                [['[4]', 'true', false], ['[7]', 'false', false], ['[0]', 'true', true], ['[-3]', 'false', true]],
                ['The % operator gives the remainder', 'Even numbers: the remainder after dividing by 2 is 0'], 'conditionals', 2, 'is_even');

            $this->exercise($python, 'Arrange: rocket countdown', 'parsons', 'python', 10,
                'Put the code lines in order so the program counts down from 3 and then prints **Liftoff!** 🚀',
                null,
                "for i in range(3, 0, -1):\n    print(i)\nprint(\"Liftoff!\")",
                [], ['The for loop goes at the top', 'Indented lines belong inside the loop'], 'loops', 1);
        }

        if ($web) {
            $this->exercise($web, 'HTML profile card', 'html', 'html', 20,
                "Build a simple profile page:\n\n- An `<h1>` heading with your name\n- A paragraph about your hobbies\n- An image with an `alt` attribute\n- A light blue page background (`lightblue`)",
                "<!doctype html>\n<html>\n  <head>\n    <style>\n      body {  }\n    </style>\n  </head>\n  <body>\n\n  </body>\n</html>\n",
                "<!doctype html>\n<html>\n  <head>\n    <style>\n      body { background-color: lightblue; }\n    </style>\n  </head>\n  <body>\n    <h1>Hi, I'm Raka</h1>\n    <p>I love playing football and making games.</p>\n    <img src=\"https://placehold.co/120\" alt=\"Photo of Raka\">\n  </body>\n</html>\n",
                [['h1', null, false, 'exists'], ['p', null, false, 'exists'], ['img', '', false, 'attr', 'alt'], ['body', 'lightblue', false, 'style', 'background-color']],
                ['The alt attribute goes inside the img tag', 'The background colour uses the background-color property'], 'html basics', 1);
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

        // Sample data: the solution is already tested.
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
