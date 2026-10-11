<?php

namespace Modules\Kanban\Actions;

use App\Models\User;
use Modules\Kanban\Models\Board;
use Modules\Kanban\Models\Project;
use Modules\Kanban\Models\TemplateTask;

/**
 * Pastikan setiap siswa aktif di kelas punya papan, dan setiap papan memuat
 * semua langkah bawaan guru. Aman dijalankan berulang kali.
 */
class SyncBoards
{
    public function handle(Project $project): void
    {
        $project->loadMissing('classRoom', 'templateTasks')->classRoom->students()->wherePivot('status', 'active')->get()
            ->each(fn (User $student) => $this->boardFor($project, $student));
    }

    public function boardFor(Project $project, User $student): Board
    {
        $project->loadMissing('templateTasks');
        $board = $project->boards()->firstOrCreate(['student_id' => $student->id]);
        $have = $board->cards()->whereNotNull('template_task_id')->pluck('template_task_id')->all();
        $next = (int) $board->cards()->where('column', 'todo')->max('position');

        $project->templateTasks->reject(fn (TemplateTask $t) => in_array($t->id, $have, true))
            ->each(function (TemplateTask $task) use ($board, &$next) {
                $board->cards()->create([
                    'template_task_id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'column' => 'todo',
                    'position' => ++$next,
                ]);
            });

        return $board;
    }
}
