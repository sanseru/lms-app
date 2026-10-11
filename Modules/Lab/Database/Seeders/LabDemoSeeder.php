<?php

namespace Modules\Lab\Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Lab\Models\LabProject;

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
            ['Shopping list', 'Still working on this one.', 'python', "items = ['milk', 'eggs']\nfor item in items:\n    print('-', item)\n", false],
        ];

        foreach ($projects as $i => [$title, $description, $language, $code, $shared]) {
            $owner = $students[$i % $students->count()];
            $project = LabProject::create(['user_id' => $owner->id, 'title' => $title, 'description' => $description, 'language' => $language, 'code' => $code]);

            if (! $shared) {
                continue;
            }

            $project->forceFill(['shared_at' => now()->subDays(5 - $i)])->save();
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
}
