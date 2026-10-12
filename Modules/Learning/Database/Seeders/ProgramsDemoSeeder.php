<?php

namespace Modules\Learning\Database\Seeders;

use Modules\Classes\Models\ClassRoom;
use Modules\Coding\Database\Seeders\CodingDemoSeeder;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\Program;

/**
 * Demo catalog that follows the client prototype (docs/client-example/LMS-Alprogmatics):
 * the nine programs of the course catalog with their tags, the client's level names
 * (Python Start 1, Python Start 2, Python Pro 1, Python Pro 2, Data Explorer 1, …),
 * numbered lessons on the map, and Lesson 1 of Python Start 1 built from the
 * prototype's own questions. Runs after the Learning and Coding demo seeders.
 */
class ProgramsDemoSeeder
{
    private int $author;

    public function run(): void
    {
        $start1 = Course::where('title', 'Python Start: Programming Basics')->firstOrFail();
        $this->author = $start1->created_by;

        // Catalog order and wording from student/course/index.html.
        $programs = collect([
            ['Data', 'Data', 'emerald', 'Turn data into useful insights.'],
            ['Artificial Intelligence', 'Technology', 'indigo', 'Discover AI basics and how it works from the very beginning.'],
            ['Python', 'Programming', 'violet', 'Start coding from scratch and build strong programming logic.'],
            ['Scratch', 'Creative', 'amber', 'Learn coding by stacking blocks and making games.'],
            ['Computer & Networks', 'Technology', 'sky', 'Understand computer devices and the basics of networks.'],
            ['Web Development', 'Web', 'sky', 'Build neat, interactive, and responsive desktop-friendly websites.'],
            ['Desktop Applications', 'Apps', 'indigo', 'Learn the basics of building desktop apps for computers.'],
            ['Android', 'Mobile', 'emerald', 'Create Android mobile apps from idea to prototype.'],
            ['Internet of Things', 'Hardware', 'rose', 'Connect sensors, devices, and data in IoT projects.'],
        ])->mapWithKeys(fn ($p, $i) => [$p[0] => Program::create(['name' => $p[0], 'category' => $p[1], 'color' => $p[2], 'description' => $p[3], 'position' => $i + 1])]);

        // Python: the three courses from the Learning seeder become client-named levels.
        $start1->update(['title' => 'Python Start 1', 'description' => 'Logic & syntax basics: print, variables, input, decisions and your first game.', 'program_id' => $programs['Python']->id, 'level_order' => 1]);
        Course::where('title', 'Scratch: Make Your First Game')->update(['title' => 'Scratch Creator 1', 'program_id' => $programs['Scratch']->id, 'level_order' => 1]);
        Course::where('title', 'Web Basics: HTML & CSS')->update(['title' => 'Web Builder 1', 'program_id' => $programs['Web Development']->id, 'level_order' => 1]);

        $this->pythonStart1($start1);
        $this->pythonLevels($programs['Python']);
        $this->otherLevels($programs);

        // The "Python Pro 1" class now uses its level; its students can also revisit the levels below.
        $pro1 = Course::where('title', 'Python Pro 1')->first();
        ClassRoom::where('name', 'Python Pro 1')->get()->each(fn ($c) => $c->forceFill(['course_id' => $pro1?->id])->saveQuietly());
    }

    /** Python Start 1: Lesson 1 from the prototype, then the existing items grouped into lessons. */
    private function pythonStart1(Course $course): void
    {
        $items = $course->lessons()->get()->keyBy('title');
        $position = 1;
        $place = function (Lesson $lesson, int $unitId) use (&$position) {
            $lesson->forceFill(['unit_id' => $unitId, 'position' => $position++])->save();
        };

        // Lesson 1: theory, "Concept Quiz night", code practice and the mixed practice split in two (client prototype).
        $lesson1 = $course->units()->create(['title' => 'Hello, Python!']);
        $place($items['Warm-up Mission'], $lesson1->id);
        $place($items['Hello, Python!'], $lesson1->id);
        $items['Hello, Python!']->update(['title' => 'Theory: Lesson 1 basics', 'body' => "## What is print()?\n\nThe `print()` function shows text on the screen. Put the text inside quotes.\n\n```python\nprint(\"Hello, world!\")\n```\n\n> Tip: lines that start with `#` are comments. Python ignores them.\n\n## Remember\n\n- Text goes inside quotes\n- Parentheses are required\n- **Python is case sensitive**"]);

        $place($this->quiz($course, 'Concept Quiz night', [
            ['Choose the correct statements', 'multiple', [['print() shows text on the screen', true], ['# starts a comment', true], ['show() prints text in Python', false], ['True is a bool value', true]]],
            ['Which function prints text to the screen?', 'single', [['show()', false], ['print()', true], ['echo()', false], ['display()', false]]],
            ['What is the result of 7 // 2 in Python?', 'single', [['3.5', false], ['4', false], ['3', true], ['1', false]]],
            ['The data type of the value True is...', 'single', [['int', false], ['str', false], ['bool', true], ['float', false]]],
        ]), $lesson1->id);

        $coding = new CodingDemoSeeder;
        $coding->exercise($course, 'Code Practice: Greeting card', 'io', 'python', 10,
            "The program below has two mistakes. Fix them so it prints a short greeting card.\n\n```\n*** Greeting Card ***\nHello, Python!\n*** Enjoy coding ***\n```",
            "Print(\"*** Greeting Card ***\")\nprint(\"Hello, Python!\"\nprint(\"*** Enjoy coding ***\")\n",
            "print(\"*** Greeting Card ***\")\nprint(\"Hello, Python!\")\nprint(\"*** Enjoy coding ***\")\n",
            [['', "*** Greeting Card ***\nHello, Python!\n*** Enjoy coding ***", false]],
            ['Python is case sensitive: print, not Print', 'Every ( needs a matching )'], 'print', 1);
        $coding->exercise($course, 'Code Practice: Calculator', 'io', 'python', 10,
            'Print the result of each calculation on its own line: `12 + 8`, `12 - 8`, `12 * 8` and `12 / 4`. Use `print()` with the expression inside.',
            "# print 12 + 8, 12 - 8, 12 * 8 and 12 / 4\n",
            "print(12 + 8)\nprint(12 - 8)\nprint(12 * 8)\nprint(12 / 4)\n",
            [['', "20\n4\n96\n3.0", false]],
            ['One print() per line', 'Division with / always gives a decimal number'], 'print', 1);
        $coding->exercise($course, 'Code Practice: Meet the user', 'io', 'python', 10,
            'The program reads a name with `input()`. Print `Hello, ` followed by the name and `!`, then print the length of the name using `len()`.',
            "name = input()\n# print \"Hello, <name>!\" and then len(name)\n",
            "name = input()\nprint(\"Hello, \" + name + \"!\")\nprint(len(name))\n",
            [['Python', "Hello, Python!\n6", false], ['Ana', "Hello, Ana!\n3", true]],
            ['Join text with +', 'len(name) counts the letters'], 'variables', 1);
        $course->lessons()->where('title', 'like', 'Code Practice:%')->orderBy('id')->get()->each(fn ($l) => $place($l, $lesson1->id));

        $place($this->quiz($course, 'Mixed Practice', [
            ['Which symbol starts a comment in Python?', 'single', [['//', false], ['#', true], ['--', false], ['/*', false]]],
            ['What does print(3 + 4) show?', 'single', [['3 + 4', false], ['7', true], ['34', false], ['Error', false]]],
        ]), $lesson1->id);

        $plan = [
            'Variables' => ['Variables and data types', 'Quiz: Variables and data types', 'Find the area of a square'],
            'Making choices' => ['If and else', 'Function: even or odd?', 'Arrange: rocket countdown'],
            'Final project' => ['Project: Number guessing game', 'Final Mission'],
        ];
        foreach ($plan as $title => $tasks) {
            $unit = $course->units()->create(['title' => $title]);
            foreach ($tasks as $task) {
                $items->get($task) && $place($items->get($task), $unit->id);
            }
        }
        $course->lessons()->whereNull('unit_id')->get()->each(fn ($l) => $l->forceFill(['position' => $position++])->save());

        // Students who already read "Hello, Python!" also finished the new Lesson 1 tasks in the demo.
        $readers = LessonProgress::where('lesson_id', $items['Hello, Python!']->id)->pluck('student_id');
        $lesson1->lessons()->pluck('id')->each(fn ($id) => $readers->each(fn ($s) => LessonProgress::markDone($id, $s)));
    }

    /** Python Start 2, Pro 1 and Pro 2 (prototype level descriptions). */
    private function pythonLevels(Program $python): void
    {
        $this->level($python, 2, 'Python Start 2', 'beginner', 'violet', 'Program flow & data structures: loops, lists and a quiz game.', [
            'Loops' => [
                ['material', 'Theory: Repeat with for', "## Doing things again and again\n\n```python\nfor i in range(3):\n    print(\"Hip hip hooray!\")\n```\n\n`range(3)` gives the numbers 0, 1 and 2, so the line runs three times."],
                ['quiz', 'Quiz: for loops', [['How many times does `for i in range(5):` repeat?', 'single', [['5', true], ['4', false], ['6', false]]], ['What is the first number in range(3)?', 'single', [['0', true], ['1', false], ['3', false]]]]],
            ],
            'Lists' => [
                ['material', 'Theory: Many values in one list', "## A list is a row of boxes\n\n```python\nfruits = [\"apple\", \"mango\", \"banana\"]\nprint(fruits[0])\n```\n\nThe first item is at position **0**."],
                ['quiz', 'Quiz: lists', [['What does `["a", "b", "c"][1]` give?', 'single', [['"b"', true], ['"a"', false], ['"c"', false]]]]],
            ],
            'Quiz game project' => [
                ['assignment', 'Project: Quiz game', "Make a quiz with at least 3 questions stored in a list. Count the score and print it at the end.\n\nUpload your `.py` file or paste a link."],
            ],
        ]);

        $this->level($python, 3, 'Python Pro 1', 'intermediate', 'violet', 'Functions, OOP & modules: write code you can reuse.', [
            'Functions' => [
                ['material', 'Theory: Make your own function', "## def makes a new command\n\n```python\ndef greet(name):\n    return \"Hi, \" + name\n\nprint(greet(\"Sari\"))\n```\n\n`return` sends a value back to the code that called the function."],
                ['quiz', 'Quiz: functions', [['Which keyword starts a function?', 'single', [['def', true], ['func', false], ['function', false]]], ['What does return do?', 'single', [['Sends a value back', true], ['Prints on the screen', false], ['Stops Python', false]]]]],
            ],
            'Classes and objects' => [
                ['material', 'Theory: Your first class', "## A class is a blueprint\n\n```python\nclass Pet:\n    def __init__(self, name):\n        self.name = name\n\n    def speak(self):\n        return self.name + \" says hi!\"\n\nprint(Pet(\"Milo\").speak())\n```"],
                ['quiz', 'Quiz: classes', [['A class is like a...', 'single', [['blueprint', true], ['loop', false], ['list', false]]]]],
            ],
            'Modules' => [
                ['material', 'Theory: Import a module', "## Use code someone already wrote\n\n```python\nimport random\nprint(random.randint(1, 6))\n```\n\nA **module** is a file full of useful functions."],
                ['quiz', 'Quiz: modules', [['Which line loads the random module?', 'single', [['import random', true], ['load random', false], ['use random', false]]]]],
            ],
        ]);

        $this->level($python, 4, 'Python Pro 2', 'advanced', 'violet', 'Projects & advanced topics: files, errors and a final app.', [
            'Files' => [
                ['material', 'Theory: Save to a file', "## Keep data after the program ends\n\n```python\nwith open(\"scores.txt\", \"w\") as f:\n    f.write(\"Aldi: 90\")\n```"],
                ['quiz', 'Quiz: files', [['Which mode writes a new file?', 'single', [['"w"', true], ['"r"', false], ['"x2"', false]]]]],
            ],
            'Handling errors' => [
                ['material', 'Theory: try and except', "## When something goes wrong\n\n```python\ntry:\n    age = int(input())\nexcept ValueError:\n    print(\"Please type a number\")\n```"],
                ['quiz', 'Quiz: errors', [['Which block runs when an error happens?', 'single', [['except', true], ['try', false], ['else', false]]]]],
            ],
            'Final app' => [
                ['assignment', 'Project: My own app', 'Build a small app of your choice (a to-do list, a quiz, a game) that uses functions, a list and a file. Share a link or upload your project.'],
            ],
        ]);
    }

    /** The other programs with the client's level names, two short lessons each. */
    private function otherLevels($programs): void
    {
        $two = fn (string $topic, string $intro) => [
            "Meet {$topic}" => [
                ['material', "Theory: What is {$topic}?", "## {$topic}\n\n{$intro}\n\nIn this level you will try it step by step, then build something small of your own."],
                ['quiz', "Quiz: {$topic} basics", [["What will you build in {$topic}?", 'single', [['Something of my own, step by step', true], ['Nothing, just reading', false]]]]],
            ],
            'Mini project' => [
                ['assignment', "Project: My first {$topic} project", "Make a small {$topic} project with what you learned. Share a link or upload a file, and tell us what it does."],
            ],
        ];

        $levels = [
            ['Data', 'Data Explorer 1', 'intermediate', 'emerald', 'Read tables, count and sort, and find simple patterns.', 'data', 'Data is facts we collect, like scores or the weather. We can sort, count and chart it to find answers.'],
            ['Data', 'Data Explorer 2', 'intermediate', 'emerald', 'Charts and stories: show what your data means.', 'charts', 'A chart turns numbers into a picture so everyone can see the answer quickly.'],
            ['Artificial Intelligence', 'Jr AI', 'beginner', 'indigo', 'What AI is, how it learns from examples, and how to use it safely.', 'AI', 'Artificial Intelligence lets computers learn from examples, like recognising cats in photos.'],
            ['Artificial Intelligence', 'Sr AI', 'intermediate', 'indigo', 'Train a small model and test how well it guesses.', 'machine learning', 'Machine learning means the computer finds the rule itself by looking at many examples.'],
            ['Scratch', 'Scratch Creator 2', 'beginner', 'amber', 'Clones, variables and levels: bigger Scratch games.', 'Scratch games', 'Use variables for the score and clones for many enemies to build a real game.'],
            ['Computer & Networks', 'Network Starter', 'beginner', 'sky', 'Parts of a computer and how devices talk to each other.', 'computers', 'A computer has a processor, memory and storage. Devices talk to each other through a network.'],
            ['Computer & Networks', 'Network Builder', 'intermediate', 'sky', 'IP addresses, Wi-Fi and staying safe online.', 'networks', 'Every device on a network has an address, like a house number, so messages reach the right place.'],
            ['Web Development', 'Web Builder 2', 'intermediate', 'sky', 'Layouts, buttons and a little JavaScript.', 'web pages', 'CSS arranges your page and JavaScript makes buttons do things when you click them.'],
            ['Desktop Applications', 'Desktop Maker 1', 'intermediate', 'indigo', 'Windows, buttons and your first desktop app.', 'desktop apps', 'A desktop app opens in its own window on a computer, with buttons and menus.'],
            ['Desktop Applications', 'Desktop Maker 2', 'intermediate', 'indigo', 'Save data and polish your app.', 'app design', 'Good apps remember your data and are easy to understand at first sight.'],
            ['Android', 'Android Starter 1', 'intermediate', 'emerald', 'Plan an app idea and build the first screen.', 'Android apps', 'An Android app is made of screens. Each screen shows information and buttons.'],
            ['Android', 'Android Starter 2', 'intermediate', 'emerald', 'Connect screens and turn your idea into a prototype.', 'app prototypes', 'A prototype is a first version you can tap through to test your idea.'],
            ['Internet of Things', 'IoT Maker 1', 'intermediate', 'rose', 'Sensors, lights and your first connected device.', 'IoT', 'The Internet of Things connects everyday objects, like lamps or plant sensors, to the internet.'],
            ['Internet of Things', 'IoT Maker 2', 'intermediate', 'rose', 'Collect sensor data and show it on a dashboard.', 'sensors', 'A sensor measures something, like temperature, and sends the number to your program.'],
        ];

        foreach ($levels as $l) {
            $program = $programs[$l[0]];
            $order = Course::where('program_id', $program->id)->max('level_order') + 1;
            $this->level($program, $order, $l[1], $l[2], $l[3], $l[4], $two($l[5], $l[6]));
        }
    }

    /** One published level with numbered lessons; each lesson holds tasks [type, title, body|questions]. */
    private function level(Program $program, int $order, string $title, string $level, string $color, string $description, array $lessons): Course
    {
        $course = Course::create([
            'program_id' => $program->id, 'level_order' => $order, 'status' => 'published', 'created_by' => $this->author,
            'title' => $title, 'subject' => $program->name, 'level' => $level, 'color' => $color, 'description' => $description,
        ]);

        $position = 1;
        foreach ($lessons as $unitTitle => $tasks) {
            $unit = $course->units()->create(['title' => $unitTitle]);
            foreach ($tasks as [$type, $taskTitle, $content]) {
                $lesson = $type === 'quiz'
                    ? $this->quiz($course, $taskTitle, $content)
                    : $course->lessons()->create(['type' => $type, 'title' => $taskTitle, 'body' => $content, 'duration_minutes' => $type === 'assignment' ? 45 : 15, 'is_published' => true, 'position' => 0]);
                $lesson->forceFill(['unit_id' => $unit->id, 'position' => $position++])->save();
            }
        }

        return $course;
    }

    /** A published quiz; questions are [prompt, type, [[option, correct], …]]. */
    private function quiz(Course $course, string $title, array $questions): Lesson
    {
        $quiz = $course->lessons()->create(['type' => 'quiz', 'title' => $title, 'pass_score' => 70, 'duration_minutes' => 10, 'is_published' => true, 'position' => $course->nextPosition()]);
        foreach ($questions as $i => [$prompt, $type, $options]) {
            $quiz->questions()->create(['position' => $i + 1, 'prompt' => $prompt, 'type' => $type, 'points' => 1,
                'options' => array_map(fn ($o) => ['text' => $o[0], 'correct' => $o[1]], $options)]);
        }

        return $quiz;
    }
}
