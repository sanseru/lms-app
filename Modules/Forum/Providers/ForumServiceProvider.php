<?php

namespace Modules\Forum\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Gate;
use Modules\Forum\Models\Answer;
use Modules\Forum\Models\Question;
use Modules\Forum\Policies\AnswerPolicy;
use Modules\Forum\Policies\QuestionPolicy;

/**
 * Modul Tanya Jawab: siswa bertanya per kelas (opsional terkait pelajaran),
 * guru dan teman sekelas menjawab, penanya atau guru memilih jawaban terbaik.
 * Orang tua bisa membaca pertanyaan anaknya.
 */
class ForumServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Forum';
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(Answer::class, AnswerPolicy::class);
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(new NavItem('Tanya Jawab', 'forum.questions.index', 'message-circle', 'Belajar', 35, active: 'forum.*'));
    }
}
