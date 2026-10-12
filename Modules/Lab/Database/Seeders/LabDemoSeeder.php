<?php

namespace Modules\Lab\Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Lab\Models\LabProject;
use ZipArchive;

/** A few Laboratory projects, most shared to the Community with likes and comments. */
class LabDemoSeeder
{
    public function run(): void
    {
        $students = User::role('student')->orderBy('id')->take(6)->get();

        if ($students->count() < 3) {
            return;
        }

        $projects = [
            ['Number guessing game', 'Guess the secret number between 1 and 10.', 'python', "import random\n\nsecret = random.randint(1, 10)\nguess = int(input(\"Your guess: \"))\nif guess == secret:\n    print(\"You got it!\")\nelse:\n    print(\"Nope, it was\", secret)\n", true],
            ['Times table', 'Prints the 7 times table.', 'python', "for i in range(1, 11):\n    print(7, 'x', i, '=', 7 * i)\n", true],
            ['My cat page', 'A web page about my cat Mochi.', 'html', "<!doctype html>\n<html>\n<head><style>body{font-family:sans-serif;text-align:center;background:#fff7ed}h1{color:#c2410c}</style></head>\n<body>\n  <h1>Meet Mochi</h1>\n  <p>Mochi is orange, fluffy, and loves boxes.</p>\n  <button onclick=\"this.textContent='Meow!'\">Pet Mochi</button>\n</body>\n</html>\n", true],
            ['Star triangle', 'Draws a triangle with stars.', 'javascript', "for (let i = 1; i <= 5; i++) {\n  console.log('*'.repeat(i));\n}\n", true],
            ['Bouncing ball', 'Press the green flag: the ball says hi and bounces around.', 'scratch', null, true],
            ['All about volcanoes', 'My science presentation.', 'presentation', "# All about volcanoes\nBy a young scientist\n\n---\n\n## What is a volcano?\n- An opening in the Earth's crust\n- Hot **magma** comes out as lava\n\n---\n\n## Famous volcanoes\n1. Mount Merapi\n2. Krakatoa\n3. Mount Fuji\n\n---\n\n# Thank you!\nAny questions?\n", true],
            ['Shopping list', 'Still working on this one.', 'python', "items = ['milk', 'eggs']\nfor item in items:\n    print('-', item)\n", false],
        ];

        foreach ($projects as $i => [$title, $description, $language, $code, $shared]) {
            $owner = $students[$i % $students->count()];
            $project = LabProject::create(['user_id' => $owner->id, 'title' => $title, 'description' => $description, 'language' => $language, 'code' => $code]);

            if ($language === 'scratch') {
                $path = "lab/{$project->id}/demo.sb3";
                Storage::disk('local')->put($path, $this->bouncingBall());
                $project->forceFill(['file_path' => $path])->save();
            }

            if (! $shared) {
                continue;
            }

            $project->forceFill(['shared_at' => now()->subDays(max(7 - $i, 0))])->save();
            $others = $students->where('id', '!=', $owner->id)->values();

            foreach ($others->take(4 - $i % 3) as $fan) {
                $project->likers()->attach($fan->id, ['created_at' => now()->subDays($i)]);
            }
            foreach ($others as $viewer) {
                DB::table('lab_project_views')->insert(['lab_project_id' => $project->id, 'user_id' => $viewer->id, 'created_at' => now()->subDays($i)]);
            }
            $project->comments()->create(['user_id' => $others[0]->id, 'body' => 'So cool! How did you make it?']);
        }
    }

    /**
     * A tiny Scratch 3 project (.sb3 = zip with project.json and the costumes), drawn here
     * so the demo has no Scratch library assets: when the green flag is clicked a ball says
     * hello, then bounces around the stage forever.
     */
    private function bouncingBall(): string
    {
        $backdrop = '<svg xmlns="http://www.w3.org/2000/svg" width="480" height="360" viewBox="0 0 480 360"><rect width="480" height="360" fill="#c4f0fd"/><rect y="300" width="480" height="60" fill="#8fd694"/></svg>';
        $ball = '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><circle cx="40" cy="40" r="36" fill="#f97b5f" stroke="#5b4185" stroke-width="4"/><circle cx="28" cy="30" r="8" fill="#fff" opacity=".7"/></svg>';
        $costume = fn (string $name, string $svg, int $x, int $y) => ['name' => $name, 'dataFormat' => 'svg', 'assetId' => md5($svg), 'md5ext' => md5($svg).'.svg', 'rotationCenterX' => $x, 'rotationCenterY' => $y];
        $block = fn (string $opcode, ?string $next, ?string $parent, array $inputs = [], bool $top = false) => ['opcode' => $opcode, 'next' => $next, 'parent' => $parent, 'inputs' => (object) $inputs, 'fields' => (object) [], 'shadow' => false, 'topLevel' => $top] + ($top ? ['x' => 40, 'y' => 40] : []);

        $project = [
            'targets' => [
                ['isStage' => true, 'name' => 'Stage', 'variables' => (object) [], 'lists' => (object) [], 'broadcasts' => (object) [], 'blocks' => (object) [], 'comments' => (object) [], 'currentCostume' => 0, 'costumes' => [$costume('sky', $backdrop, 240, 180)], 'sounds' => [], 'volume' => 100, 'layerOrder' => 0, 'tempo' => 60, 'videoTransparency' => 50, 'videoState' => 'on', 'textToSpeechLanguage' => null],
                ['isStage' => false, 'name' => 'Ball', 'variables' => (object) [], 'lists' => (object) [], 'broadcasts' => (object) [], 'comments' => (object) [], 'currentCostume' => 0, 'costumes' => [$costume('ball', $ball, 40, 40)], 'sounds' => [], 'volume' => 100, 'layerOrder' => 1, 'visible' => true, 'x' => 0, 'y' => 0, 'size' => 100, 'direction' => 45, 'draggable' => false, 'rotationStyle' => 'all around',
                    'blocks' => [
                        'flag' => $block('event_whenflagclicked', 'say', null, [], true),
                        'say' => $block('looks_sayforsecs', 'loop', 'flag', ['MESSAGE' => [1, [10, 'Hello!']], 'SECS' => [1, [4, '1']]]),
                        'loop' => $block('control_forever', null, 'say', ['SUBSTACK' => [2, 'move']]),
                        'move' => $block('motion_movesteps', 'bounce', 'loop', ['STEPS' => [1, [4, '8']]]),
                        'bounce' => $block('motion_ifonedgebounce', null, 'move'),
                    ],
                ],
            ],
            'monitors' => [],
            'extensions' => [],
            'meta' => ['semver' => '3.0.0', 'vm' => '15.2.0', 'agent' => 'Alprogmatics demo seeder'],
        ];

        $file = tempnam(sys_get_temp_dir(), 'sb3');
        $zip = new ZipArchive;
        $zip->open($file, ZipArchive::OVERWRITE);
        $zip->addFromString('project.json', json_encode($project, JSON_UNESCAPED_SLASHES));
        $zip->addFromString(md5($backdrop).'.svg', $backdrop);
        $zip->addFromString(md5($ball).'.svg', $ball);
        $zip->close();

        $data = file_get_contents($file);
        unlink($file);

        return $data;
    }
}
