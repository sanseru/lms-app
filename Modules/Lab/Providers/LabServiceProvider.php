<?php

namespace Modules\Lab\Providers;

use App\Models\User;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Gamification\Support\QueryRule;
use Modules\Gamification\Support\XpRules;
use Modules\Lab\Models\LabComment;
use Modules\Lab\Models\LabProject;
use Modules\Lab\Policies\LabCommentPolicy;
use Modules\Lab\Policies\LabProjectPolicy;

/**
 * Laboratory + Community. In the Laboratory anyone who codes (students,
 * teachers) keeps free-form projects in Python, JavaScript, or HTML/CSS and
 * runs them in the browser with the Coding module's runners. A project can be
 * shared to the Community, a gallery across all classes where people like,
 * view, and comment. Teachers and admins moderate.
 */
class LabServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Lab';
    }

    public function permissions(): array
    {
        return [
            'lab.use' => ['admin', 'teacher', 'student'],
            'community.moderate' => ['admin', 'teacher'],
        ];
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(LabProject::class, LabProjectPolicy::class);
        Gate::policy(LabComment::class, LabCommentPolicy::class);

        // XP and the client's badges (First project, 3 projects, First like, First comment).
        $this->callAfterResolving(XpRules::class, fn (XpRules $rules) => $rules->add(
            new QueryRule('lab_shared', 'Shared a project', 15, fn (User $u) => DB::table('lab_projects')
                ->where('user_id', $u->id)->whereNotNull('shared_at')->whereNull('hidden_at')->select('id as source_id', 'shared_at as at')),
            // One event per project that someone else liked, so likes from friends can't pile up XP.
            new QueryRule('lab_liked', 'Got a like on a project', 5, fn (User $u) => DB::table('lab_project_likes')
                ->join('lab_projects', 'lab_projects.id', '=', 'lab_project_likes.lab_project_id')
                ->where('lab_projects.user_id', $u->id)->where('lab_project_likes.user_id', '!=', $u->id)->whereNull('lab_projects.hidden_at')
                ->groupBy('lab_projects.id')->selectRaw('lab_projects.id as source_id, min(lab_project_likes.created_at) as at')),
            new QueryRule('lab_commented', "Commented on a classmate's project", 2, fn (User $u) => DB::table('lab_comments')
                ->join('lab_projects', 'lab_projects.id', '=', 'lab_comments.lab_project_id')
                ->where('lab_comments.user_id', $u->id)->where('lab_projects.user_id', '!=', $u->id)
                ->select('lab_comments.id as source_id', 'lab_comments.created_at as at')),
        ));
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Laboratory', 'lab.projects.index', 'flask', 'Learning', 33, permission: 'lab.use', active: 'lab.*'),
            new NavItem('Community', 'community.index', 'users', 'Learning', 32, active: 'community.*'),
        );
    }
}
