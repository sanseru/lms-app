<?php

namespace Modules\Lab\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Gate;
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
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Laboratory', 'lab.projects.index', 'flask', 'Learning', 33, permission: 'lab.use', active: 'lab.*'),
            new NavItem('Community', 'community.index', 'users', 'Learning', 32, active: 'community.*'),
        );
    }
}
