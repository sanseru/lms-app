<?php

namespace Modules\Kanban\Database\Seeders;

use Modules\Classes\Models\ClassRoom;
use Modules\Kanban\Actions\MoveCard;
use Modules\Kanban\Actions\SyncBoards;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Project;

/** Sample projects with student boards at different stages. */
class KanbanDemoSeeder
{
    public function run(): void
    {
        $sync = app(SyncBoards::class);
        $move = app(MoveCard::class);

        $samples = [
            ['Scratch', '🐱', 'Catch the Ball Game', "Make a simple game: a basket catches balls falling from the top.\n\nIt should at least have a score and a sound when a ball is caught. Share your Scratch project link in the Project result section.", 10, true,
                ['Write down your game idea and rules', 'Pick the basket and ball sprites', 'Make the basket move with the arrow keys', 'Drop the ball from a random spot', 'Add a score and sounds', 'Test it with a friend']],
            ['Python', '🐍', 'Pocket Money Calculator', 'Write a Python program that works out how much pocket money is left after a few purchases.', 14, true,
                ['Write down what your program does', 'Ask for the starting pocket money', 'Use a while loop to ask for each price', 'Print the money left and a warning if it goes below zero', 'Test it with 3 examples']],
            ['Web Development', '🌐', 'My Hobby Website', 'Build a one-page website about your hobby with HTML and CSS.', 21, false,
                ['Sketch the page on paper', 'Build the HTML structure', 'Add pictures with alt text', 'Style it with CSS', 'Publish it and share the link']],
        ];

        foreach ($samples as [$subject, $emoji, $title, $description, $days, $review, $tasks]) {
            $class = ClassRoom::where('subject', $subject)->with('teacher')->first();
            if (! $class) {
                continue;
            }

            $project = Project::create([
                'class_room_id' => $class->id, 'created_by' => $class->teacher_id, 'title' => $title, 'description' => $description,
                'emoji' => $emoji, 'due_date' => now()->addDays($days)->toDateString(), 'requires_review' => $review,
            ]);
            foreach ($tasks as $i => $task) {
                $project->templateTasks()->create(['title' => $task, 'position' => $i + 1]);
            }
            $sync->handle($project->load('templateTasks'));

            // Each student is at a different stage.
            $project->boards()->with('cards', 'student', 'project.classRoom')->get()->each(function (Board $board, $n) use ($move, $class, $review) {
                $cards = $board->cards->values();
                $done = min($cards->count(), [1, 3, $cards->count(), 2, 0][$n % 5]);

                foreach ($cards->take($done) as $card) {
                    $move->handle($card, 'done', 99, $review ? $class->teacher : $board->student);
                }
                if ($cards->has($done)) {
                    $move->handle($cards[$done], $review && $n % 2 ? 'review' : 'doing', 0, $board->student);
                }
                if ($n === 0 && $cards->has($done + 1)) {
                    $cards[$done + 1]->update(['feedback' => 'Nice work! Try adding a sound when the score goes up too.']);
                    $move->handle($cards[$done + 1], 'doing', 1, $class->teacher);
                }
                if ($done === $cards->count()) {
                    $board->update(['result_url' => 'https://scratch.mit.edu/projects/1000000'.$board->id, 'result_note' => 'The most fun part: making the ball fall faster and faster!']);
                }
            });
        }
    }
}
