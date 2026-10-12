<?php

namespace Modules\Lab\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Lab\Http\Requests\LabProjectRequest;
use Modules\Lab\Models\LabProject;

class LabProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $language = array_key_exists($request->query('language'), LabProject::LANGUAGES) ? $request->query('language') : null;

        $projects = LabProject::query()
            ->where('user_id', $user->id)
            ->when($language, fn ($q) => $q->where('language', $language))
            ->with('owner:id,name')
            ->withCount(['likers', 'viewers', 'comments'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Lab::Lab/Index', [
            'projects' => $projects->map->toCard($user),
            'filters' => ['language' => $language],
            'types' => collect(LabProject::LANGUAGES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'description' => LabProject::DESCRIPTIONS[$key]])->values(),
            'totals' => [
                'projects' => LabProject::where('user_id', $user->id)->count(),
                'shared' => LabProject::where('user_id', $user->id)->inCommunity()->count(),
            ],
        ]);
    }

    public function store(LabProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $project = LabProject::create([
            ...$data,
            'user_id' => $request->user()->id,
            // Scratch starts from Scratch's own default project, saved on the first Save.
            'code' => in_array($data['language'], LabProject::FILE_TYPES, true) ? null : ($data['code'] ?? LabProject::STARTERS[$data['language']] ?? ''),
        ]);

        return to_route('lab.projects.edit', $project);
    }

    public function edit(Request $request, LabProject $project): Response
    {
        abort_unless($project->isOwnedBy($request->user()), 403);
        $project->load('owner:id,name')->loadCount(['likers', 'viewers', 'comments']);

        return Inertia::render('Lab::Lab/Editor', [
            'project' => $project->toCard($request->user()) + ['code' => $project->code ?? ''],
            'runtime' => ['pyodide_url' => config('coding.pyodide_url', '/vendor/pyodide/')],
        ]);
    }

    public function update(LabProjectRequest $request, LabProject $project): RedirectResponse
    {
        $data = $request->safe()->except('file');
        $project->update($project->usesFile() ? ['code' => null] + $data : $data);

        if ($file = $request->file('file')) {
            $project->storeFile($file);
        }

        return back()->with('success', 'Project saved.');
    }

    public function destroy(Request $request, LabProject $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 403);
        $project->delete();

        return to_route('lab.projects.index')->with('success', 'Project deleted.');
    }

    public function share(Request $request, LabProject $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 403);

        if ($project->hidden_at) {
            return back()->with('error', 'A teacher removed this project from the Community, so it can’t be shared again.');
        }

        $project->forceFill(['shared_at' => $project->shared_at ?? now()])->save();

        return back()->with('success', 'Shared to the Community. Everyone can see and run it now.');
    }

    public function unshare(Request $request, LabProject $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 403);
        $project->forceFill(['shared_at' => null])->save();

        return back()->with('success', 'Your project is private again.');
    }
}
