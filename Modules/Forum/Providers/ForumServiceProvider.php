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
 * Q&A module: students ask questions per class (optionally tied to a lesson),
 * teachers and classmates answer, and the asker or teacher picks the best answer.
 * Parents can read their child's questions.
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
        $nav->add(new NavItem('Q&A', 'forum.questions.index', 'message-circle', 'Learning', 35, active: 'forum.*'));
    }
}
