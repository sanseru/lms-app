<?php

namespace Modules\Gamification\Providers;

use App\Models\User;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\DB;
use Modules\Coding\Providers\CodingServiceProvider;
use Modules\Forum\Providers\ForumServiceProvider;
use Modules\Gamification\Console\SyncXpCommand;
use Modules\Gamification\Support\QueryRule;
use Modules\Gamification\Support\XpRules;
use Modules\Kanban\Providers\KanbanServiceProvider;
use Modules\Learning\Providers\LearningServiceProvider;
use Modules\Sessions\Providers\SessionsServiceProvider;

/**
 * Gamification module: XP, levels and badges calculated from learning activity
 * in other modules. Other modules can add their own XP rules via
 * app(XpRules::class)->add(new QueryRule(...)).
 */
class GamificationServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Gamification';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config.php'), 'gamification');

        $this->app->singleton(XpRules::class, function () {
            $rules = new XpRules;
            $this->defaultRules($rules);

            return $rules;
        });
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([SyncXpCommand::class]);
        }
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('My achievements', 'gamification.me', 'trophy', 'Learning', 36, roles: ['student'], active: 'gamification.*'),
            new NavItem("Child's achievements", 'gamification.me', 'trophy', 'Learning', 36, roles: ['guardian'], active: 'gamification.*'),
            new NavItem('Student achievements', 'gamification.classes', 'trophy', 'Learning', 36, roles: ['admin', 'teacher'], active: 'gamification.*'),
        );
    }

    private function enabled(string $provider): bool
    {
        return in_array($provider, config('modules.enabled', []), true);
    }

    private function defaultRules(XpRules $rules): void
    {
        if ($this->enabled(LearningServiceProvider::class)) {
            $rules->add(
                new QueryRule('lesson_done', 'Finished a lesson', 10, fn (User $u) => DB::table('lesson_progress')
                    ->where('student_id', $u->id)->select('lesson_id as source_id', 'completed_at as at')),
                new QueryRule('quiz_passed', 'Passed a quiz', 20, fn (User $u) => DB::table('quiz_attempts')
                    ->where('student_id', $u->id)->where('passed', true)->groupBy('lesson_id')
                    ->selectRaw('lesson_id as source_id, min(created_at) as at')),
                new QueryRule('quiz_perfect', 'Scored 100 on a quiz', 10, fn (User $u) => DB::table('quiz_attempts')
                    ->where('student_id', $u->id)->where('score', 100)->groupBy('lesson_id')
                    ->selectRaw('lesson_id as source_id, min(created_at) as at')),
                new QueryRule('assignment_graded', 'Assignment graded', 25, fn (User $u) => DB::table('assignment_submissions')
                    ->where('student_id', $u->id)->where('status', 'graded')->select('id as source_id', 'graded_at as at')),
                // Milestone only (0 XP, the lesson already gave XP): the client's "Python starter" badge.
                new QueryRule('python_lesson', 'Finished a Python lesson', 0, fn (User $u) => DB::table('lesson_progress')
                    ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
                    ->join('courses', 'courses.id', '=', 'lessons.course_id')
                    ->join('programs', 'programs.id', '=', 'courses.program_id')
                    ->where('lesson_progress.student_id', $u->id)->whereLike('programs.name', 'Python%')
                    ->select('lesson_progress.lesson_id as source_id', 'lesson_progress.completed_at as at')),
            );
        }

        if ($this->enabled(CodingServiceProvider::class)) {
            $rules->add(new QueryRule('code_passed', 'Passed a coding exercise', 25, fn (User $u) => DB::table('code_submissions')
                ->where('student_id', $u->id)->where('status', 'passed')->groupBy('lesson_id')
                ->selectRaw('lesson_id as source_id, min(created_at) as at')));
        }

        if ($this->enabled(SessionsServiceProvider::class)) {
            $rules->add(new QueryRule('attended', 'Attended class', 15, fn (User $u) => DB::table('session_attendances')
                ->join('class_sessions', 'class_sessions.id', '=', 'session_attendances.class_session_id')
                ->where('session_attendances.student_id', $u->id)->where('session_attendances.status', 'present')
                ->select('class_sessions.id as source_id', 'class_sessions.starts_at as at')));
        }

        if ($this->enabled(ForumServiceProvider::class)) {
            $rules->add(
                new QueryRule('forum_asked', 'Asked in Q&A', 5, fn (User $u) => DB::table('forum_questions')
                    ->where('author_id', $u->id)->select('id as source_id', 'created_at as at')),
                new QueryRule('forum_answer_accepted', 'Answer picked as best', 20, fn (User $u) => DB::table('forum_answers')
                    ->join('forum_questions', 'forum_questions.accepted_answer_id', '=', 'forum_answers.id')
                    ->where('forum_answers.author_id', $u->id)->where('forum_questions.author_id', '!=', $u->id)
                    ->select('forum_answers.id as source_id', 'forum_questions.resolved_at as at')),
            );
        }

        if ($this->enabled(KanbanServiceProvider::class)) {
            $rules->add(
                new QueryRule('card_done', 'Finished a project step', 10, fn (User $u) => DB::table('project_cards')
                    ->join('project_boards', 'project_boards.id', '=', 'project_cards.board_id')
                    ->where('project_boards.student_id', $u->id)->where('project_cards.column', 'done')
                    ->select('project_cards.id as source_id', 'project_cards.done_at as at')),
                new QueryRule('project_completed', 'Finished a project', 50, fn (User $u) => DB::table('project_boards')
                    ->where('student_id', $u->id)->whereNotNull('completed_at')->select('id as source_id', 'completed_at as at')),
            );
        }
    }
}
