<?php

namespace Modules\Lab\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Lab\Http\Requests\LabCommentRequest;
use Modules\Lab\Models\LabComment;
use Modules\Lab\Models\LabProject;

/** Community gallery: shared Laboratory projects from every class. */
class CommunityController extends Controller
{
    private const SORTS = ['new', 'popular', 'discussed'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'new';
        $language = array_key_exists($request->query('language'), LabProject::LANGUAGES) ? $request->query('language') : null;
        $search = trim((string) $request->query('q'));

        $base = fn () => LabProject::query()->inCommunity()
            ->with('owner:id,name')
            ->withCount(['likers', 'viewers', 'comments'])
            ->withExists(['likers as liked' => fn (Builder $q) => $q->whereKey($user->id)]);

        $projects = $base()
            ->when($language, fn ($q) => $q->where('language', $language))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('likers_count')->orderByDesc('viewers_count'))
            ->when($sort === 'discussed', fn ($q) => $q->orderByDesc('comments_count'))
            ->orderByDesc('shared_at')
            ->paginate(12)
            ->withQueryString();

        // Trending: most likes received in the last 7 days.
        $trending = $base()
            ->whereHas('likers', $recent = fn (Builder $q) => $q->where('lab_project_likes.created_at', '>=', now()->subDays(7)))
            ->withCount(['likers as recent_likes' => $recent])
            ->orderByDesc('recent_likes')
            ->orderByDesc('shared_at')
            ->limit(6)
            ->get();

        return Inertia::render('Lab::Community/Index', [
            'projects' => $projects->through(fn (LabProject $p) => $p->toCard($user)),
            'trending' => $trending->map->toCard($user),
            'filters' => ['sort' => $sort, 'language' => $language, 'q' => $search],
            'languages' => LabProject::LANGUAGES,
            'can' => ['create' => $user->can('lab.use')],
        ]);
    }

    public function show(Request $request, LabProject $project): Response
    {
        Gate::authorize('view', $project);
        $user = $request->user();

        if ($project->user_id !== $user->id && $project->isShared()) {
            DB::table('lab_project_views')->insertOrIgnore(['lab_project_id' => $project->id, 'user_id' => $user->id, 'created_at' => now()]);
        }

        $project->load(['owner:id,name', 'comments.author:id,name'])
            ->loadCount(['likers', 'viewers', 'comments'])
            ->loadExists(['likers as liked' => fn (Builder $q) => $q->whereKey($user->id)]);

        return Inertia::render('Lab::Community/Show', [
            'project' => $project->toCard($user) + ['code' => $project->code ?? ''],
            'comments' => $project->comments->map(fn (LabComment $c) => [
                'id' => $c->id,
                'body' => $c->body,
                'author' => ['id' => $c->user_id, 'name' => LabProject::publicName($c->author), 'initials' => $c->author?->initials() ?? '?'],
                'created_at' => $c->created_at->toIso8601String(),
                'can_delete' => $user->can('delete', $c),
            ]),
            'runtime' => ['pyodide_url' => config('coding.pyodide_url', '/vendor/pyodide/')],
            'can' => [
                'interact' => $user->can('interact', $project),
                'edit' => $user->can('update', $project),
                'moderate' => $user->can('moderate', $project),
            ],
        ]);
    }

    public function like(Request $request, LabProject $project): RedirectResponse
    {
        Gate::authorize('interact', $project);
        $project->likers()->toggle([$request->user()->id => ['created_at' => now()]]);

        return back();
    }

    public function comment(LabCommentRequest $request, LabProject $project): RedirectResponse
    {
        $project->comments()->create(['user_id' => $request->user()->id, 'body' => $request->validated('body')]);

        return back()->with('success', 'Comment posted.');
    }

    public function destroyComment(LabComment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);
        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    public function hide(Request $request, LabProject $project): RedirectResponse
    {
        Gate::authorize('moderate', $project);
        $project->forceFill(['hidden_at' => now(), 'hidden_by' => $request->user()->id])->save();

        return to_route('community.index')->with('success', 'Project removed from the Community.');
    }

    public function restore(LabProject $project): RedirectResponse
    {
        Gate::authorize('moderate', $project);
        $project->forceFill(['hidden_at' => null, 'hidden_by' => null])->save();

        return back()->with('success', 'Project is back in the Community.');
    }
}
