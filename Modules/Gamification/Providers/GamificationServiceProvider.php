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
 * Modul Gamifikasi: XP, level, dan lencana yang dihitung dari aktivitas
 * belajar di modul lain. Modul lain bisa menambah aturan XP sendiri lewat
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
            new NavItem('Prestasiku', 'gamification.me', 'trophy', 'Belajar', 36, roles: ['student'], active: 'gamification.*'),
            new NavItem('Prestasi Anak', 'gamification.me', 'trophy', 'Belajar', 36, roles: ['guardian'], active: 'gamification.*'),
            new NavItem('Prestasi Siswa', 'gamification.classes', 'trophy', 'Belajar', 36, roles: ['admin', 'teacher'], active: 'gamification.*'),
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
                new QueryRule('lesson_done', 'Menyelesaikan pelajaran', 10, fn (User $u) => DB::table('lesson_progress')
                    ->where('student_id', $u->id)->select('lesson_id as source_id', 'completed_at as at')),
                new QueryRule('quiz_passed', 'Lulus kuis', 20, fn (User $u) => DB::table('quiz_attempts')
                    ->where('student_id', $u->id)->where('passed', true)->groupBy('lesson_id')
                    ->selectRaw('lesson_id as source_id, min(created_at) as at')),
                new QueryRule('quiz_perfect', 'Nilai 100 di kuis', 10, fn (User $u) => DB::table('quiz_attempts')
                    ->where('student_id', $u->id)->where('score', 100)->groupBy('lesson_id')
                    ->selectRaw('lesson_id as source_id, min(created_at) as at')),
                new QueryRule('assignment_graded', 'Tugas dinilai guru', 25, fn (User $u) => DB::table('assignment_submissions')
                    ->where('student_id', $u->id)->where('status', 'graded')->select('id as source_id', 'graded_at as at')),
            );
        }

        if ($this->enabled(CodingServiceProvider::class)) {
            $rules->add(new QueryRule('code_passed', 'Lulus soal coding', 25, fn (User $u) => DB::table('code_submissions')
                ->where('student_id', $u->id)->where('status', 'passed')->groupBy('lesson_id')
                ->selectRaw('lesson_id as source_id, min(created_at) as at')));
        }

        if ($this->enabled(SessionsServiceProvider::class)) {
            $rules->add(new QueryRule('attended', 'Hadir di kelas', 15, fn (User $u) => DB::table('session_attendances')
                ->join('class_sessions', 'class_sessions.id', '=', 'session_attendances.class_session_id')
                ->where('session_attendances.student_id', $u->id)->where('session_attendances.status', 'present')
                ->select('class_sessions.id as source_id', 'class_sessions.starts_at as at')));
        }

        if ($this->enabled(ForumServiceProvider::class)) {
            $rules->add(
                new QueryRule('forum_asked', 'Bertanya di Tanya Jawab', 5, fn (User $u) => DB::table('forum_questions')
                    ->where('author_id', $u->id)->select('id as source_id', 'created_at as at')),
                new QueryRule('forum_answer_accepted', 'Jawaban terpilih terbaik', 20, fn (User $u) => DB::table('forum_answers')
                    ->join('forum_questions', 'forum_questions.accepted_answer_id', '=', 'forum_answers.id')
                    ->where('forum_answers.author_id', $u->id)->where('forum_questions.author_id', '!=', $u->id)
                    ->select('forum_answers.id as source_id', 'forum_questions.resolved_at as at')),
            );
        }

        if ($this->enabled(KanbanServiceProvider::class)) {
            $rules->add(
                new QueryRule('card_done', 'Langkah proyek selesai', 10, fn (User $u) => DB::table('project_cards')
                    ->join('project_boards', 'project_boards.id', '=', 'project_cards.board_id')
                    ->where('project_boards.student_id', $u->id)->where('project_cards.column', 'done')
                    ->select('project_cards.id as source_id', 'project_cards.done_at as at')),
                new QueryRule('project_completed', 'Proyek selesai', 50, fn (User $u) => DB::table('project_boards')
                    ->where('student_id', $u->id)->whereNotNull('completed_at')->select('id as source_id', 'completed_at as at')),
            );
        }
    }
}
