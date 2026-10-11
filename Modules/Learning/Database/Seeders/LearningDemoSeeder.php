<?php

namespace Modules\Learning\Database\Seeders;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Actions\GradeQuiz;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;

/** Sample courses with student progress, called from DemoSeeder. */
class LearningDemoSeeder
{
    /** @param  Collection<int, ClassRoom>  $classes */
    public function run(User $author, Collection $classes): void
    {
        $python = $this->course($author, 'Python Start: Programming Basics', 'Python', 'beginner', 'violet',
            'Learn Python from scratch: variables, input, conditionals and loops, then build a number guessing game.', [
                ['material', 'Hello, Python!', 15, "## What is Python?\n\nPython is a programming language that is easy to read. People use it for games, websites and even artificial intelligence.\n\nOur first program:\n\n```python\nprint(\"Hello, world!\")\n```\n\n> Tip: the `print` command shows text on the screen.\n\n### Try it yourself\n- Change the text to your name\n- Add a second `print` line", 'https://www.youtube.com/watch?v=kqtD5dpn9C8'],
                ['material', 'Variables and data types', 20, "## A variable is like a box\n\nA variable stores a value so you can use it again.\n\n```python\nname = \"Aldi\"\nage = 10\nheight = 1.4\n```\n\n| Type | Example |\n|---|---|\n| `str` (text) | `\"hello\"` |\n| `int` (whole number) | `10` |\n| `float` (decimal) | `1.4` |\n| `bool` | `True` / `False` |"],
                ['quiz', 'Quiz: Variables and data types', 10, 'Answer every question. The pass score is 70 and you can try again.', null, [
                    ['The command that shows text on the screen is…', 'single', [['print()', true], ['input()', false], ['show()', false], ['write()', false]], 'print() shows text or a value on the screen.'],
                    ['The data type of the value 3.14 is…', 'single', [['int', false], ['float', true], ['str', false], ['bool', false]], 'Numbers with a decimal point are floats.'],
                    ['Which of these are valid variable names in Python?', 'multiple', [['player_score', true], ['2score', false], ['myName', true], ['final score', false]], 'A variable name cannot start with a number or contain spaces.'],
                    ['What does `print(3 + 4 * 2)` show?', 'single', [['14', false], ['11', true], ['10', false]], 'Multiplication comes first: 4 × 2 = 8, then 3 + 8 = 11.'],
                ]],
                ['material', 'If and else', 20, "## Programs that can choose\n\n```python\nscore = 80\nif score >= 75:\n    print(\"You passed!\")\nelse:\n    print(\"Let's study some more\")\n```\n\nWatch the **indentation** (the spaces at the start of a line). Python uses it to know which lines belong inside the `if`."],
                ['assignment', 'Project: Number guessing game', 45, "Build a game where you guess a number from 1 to 10.\n\n1. The computer picks a random number with `random.randint(1, 10)`\n2. The player keeps guessing until they get it right\n3. Give a hint: *too high* or *too low*\n\nSubmit your Replit link or upload your `.py` file."],
            ]);

        $scratch = $this->course($author, 'Scratch: Make Your First Game', 'Scratch', 'beginner', 'amber',
            'Learn logic with colorful blocks and build your own chase game.', [
                ['material', 'Meet Scratch', 15, "## Stage, sprites and blocks\n\n- **Stage**: where everything happens\n- **Sprite**: a character that can move\n- **Code blocks**: commands you snap together like Lego\n\nOpen [scratch.mit.edu](https://scratch.mit.edu) and click *Create*."],
                ['material', 'Moving your character', 20, "## Move with the arrow keys\n\nUse the **when [right arrow] key pressed** block, then **change x by 10**.\n\nTry making your character move in all 4 directions."],
                ['quiz', 'Quiz: Scratch basics', 8, null, null, [
                    ['In Scratch, a character that can move is called a…', 'single', [['Sprite', true], ['Stage', false], ['Block', false]], 'A sprite is a character or object on the stage.'],
                    ['The block that moves a sprite to the right is…', 'single', [['change x by 10', true], ['change y by 10', false], ['turn 15 degrees', false]], 'The x axis goes left and right.'],
                    ['Which blocks belong to the Events category?', 'multiple', [['when green flag clicked', true], ['when key pressed', true], ['move 10 steps', false]], 'Events are what start a script.'],
                ]],
                ['assignment', 'Project: Chase game', 60, "Make a game where a cat chases a ball.\n\n- Move the cat with the arrow keys\n- The ball jumps to a random spot when the cat touches it\n- Add a score\n\nSubmit the link to your Scratch project (make sure you have clicked *Share*)."],
            ]);

        $web = $this->course($author, 'Web Basics: HTML & CSS', 'Web Development', 'intermediate', 'sky',
            'Build your first personal web page with HTML and make it look great with CSS.', [
                ['material', 'HTML page structure', 20, "## The HTML skeleton\n\n```html\n<!doctype html>\n<html>\n  <body>\n    <h1>Hi, this is my website</h1>\n    <p>I love coding.</p>\n  </body>\n</html>\n```"],
                ['quiz', 'Quiz: HTML tags', 8, null, null, [
                    ['The tag for the biggest heading is…', 'single', [['<h1>', true], ['<title>', false], ['<head>', false]], '<h1> is a level 1 heading.'],
                    ['The tag for a paragraph is…', 'single', [['<p>', true], ['<para>', false], ['<text>', false]], null],
                ]],
                ['assignment', 'Project: Profile page', 60, 'Build a profile page with your name, hobbies and a photo. Submit a CodePen link or upload your `.html` file.'],
            ]);

        // Attach the courses to classes.
        $map = ['Python Start 1' => $python, 'Scratch Game Maker' => $scratch, 'Web Development Junior' => $web];
        foreach ($classes as $class) {
            if (isset($map[$class->name])) {
                $class->forceFill(['course_id' => $map[$class->name]->id])->saveQuietly();
            }
        }

        $this->simulateProgress($classes->filter(fn ($c) => isset($map[$c->name])), $map);
        $this->missions($python, $classes->firstWhere('name', 'Python Start 1'));
    }

    /** Warm-up Mission (start) and Final Mission for the Python course, with a concept per question. */
    private function missions(Course $course, ClassRoom $class): void
    {
        $make = function (string $role, string $title, array $questions, int $position) use ($course) {
            $lesson = $course->lessons()->create([
                'title' => $title, 'type' => 'quiz', 'assessment' => $role, 'position' => $position, 'pass_score' => 0, 'duration_minutes' => 10,
                'body' => $role === 'pre' ? 'A short mission to see where you are starting from. It does not affect your grade.' : 'A closing mission to see how far you have come.',
            ]);
            foreach ($questions as $i => [$prompt, $concept, $options]) {
                $lesson->questions()->create(['position' => $i + 1, 'prompt' => $prompt, 'type' => 'single', 'points' => 1, 'concept' => $concept,
                    'options' => array_map(fn ($o) => ['text' => $o[0], 'correct' => $o[1]], $options)]);
            }

            return $lesson->load('questions');
        };

        // The warm-up mission goes first.
        $course->lessons()->increment('position');
        $pre = $make('pre', 'Warm-up Mission', [
            ['What does `print(2 + 3)` show?', 'variables', [['5', true], ['23', false], ['2 + 3', false]]],
            ['To store a player\'s name, we use a…', 'variables', [['variable', true], ['loop', false], ['comment', false]]],
            ['`if score > 70:` means…', 'conditionals', [['if the score is more than 70', true], ['repeat 70 times', false], ['the score equals 70', false]]],
            ['How many times does `for i in range(3):` repeat?', 'loops', [['3', true], ['2', false], ['4', false]]],
        ], 1);
        $post = $make('post', 'Final Mission', [
            ['What does `print(4 * 2)` show?', 'variables', [['8', true], ['42', false], ['4 * 2', false]]],
            ['The values `True` and `False` have the type…', 'variables', [['bool', true], ['int', false], ['str', false]]],
            ['When does the `else` block run?', 'conditionals', [['when the if condition is false', true], ['always', false], ['when the if condition is true', false]]],
            ['How many times does `for i in range(1, 5):` repeat?', 'loops', [['4', true], ['5', false], ['1', false]]],
        ], $course->nextPosition());

        $grade = app(GradeQuiz::class);
        foreach ($class->students()->get() as $i => $student) {
            // Warm-up: only the first question right (and the second for some students).
            $grade->handle($pre, $student, $pre->questions->mapWithKeys(fn ($q, $j) => [$q->id => $j === 0 || ($j === 1 && $i % 2 === 0) ? 0 : 1])->all());
            // Final mission: mostly right.
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

    /** Students have finished some lessons; some assignments are graded, some are waiting. */
    private function simulateProgress(Collection $classes, array $map): void
    {
        $grade = app(GradeQuiz::class);

        foreach ($classes as $class) {
            $lessons = $map[$class->name]->lessons()->with('questions')->get();

            foreach ($class->students()->get() as $s => $student) {
                $reach = min($lessons->count(), 2 + ($student->id % 3)); // how far this student has got

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
        // First attempt gets one question wrong; the second (for some students) gets everything right.
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
            'body' => 'All done! I added a score and a sound when you win.',
            // Demo data without outside links: a fake link would point to a page that doesn't exist.
            'link_url' => null,
            'status' => $graded ? 'graded' : 'submitted',
            'score' => $graded ? 80 + ($student->id % 4) * 5 : null,
            'feedback' => $graded ? 'Awesome! Try adding a limit on the number of guesses to make it more challenging.' : null,
            'graded_by' => $graded ? $class->teacher_id : null,
            'graded_at' => $graded ? now()->subDays(2) : null,
            'submitted_at' => now()->subDays(3 + $index),
        ]);
        LessonProgress::markDone($lesson->id, $student->id);
    }
}
