<?php

namespace Modules\Leads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Identity\Enums\Role;
use Modules\Leads\Models\Lead;
use Modules\Notifications\Support\Notify;

/** Teachers see the trial classes assigned to them and record the results. */
class TrialController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Lead::query()->whereNotNull('trial_at')
            ->when(! $user->hasRole(Role::Admin->value), fn ($q) => $q->where('trial_teacher_id', $user->id))
            ->with(['trialTeacher:id,name', 'recommendedClass:id,name']);

        $map = fn (Lead $l) => [
            ...$l->toCard(),
            'child_grade' => $l->child_grade,
            'preferred_time' => $l->preferred_time,
            'message' => $l->message,
            'zoom_url' => $l->trial_zoom_url,
            'trial_result' => $l->trial_result,
            'trial_interest' => $l->trial_interest,
            'recommended_class_id' => $l->recommended_class_id,
            'recommended_class' => $l->recommendedClass?->name,
            'is_past' => $l->trial_at->isPast(),
        ];

        return Inertia::render('Leads::Trials', [
            'upcoming' => (clone $query)->where('status', 'trial_scheduled')->orderBy('trial_at')->get()->map($map),
            'done' => (clone $query)->whereNotNull('trial_result')->latest('trial_at')->take(20)->get()->map($map),
            'classes' => ClassRoom::whereIn('status', ['active', 'upcoming'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function result(Request $request, Lead $lead): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::Admin->value) || $lead->trial_teacher_id === $user->id, 403);
        abort_unless(in_array($lead->status, ['trial_scheduled', 'trial_done'], true), 422);

        $data = $request->validate([
            'trial_result' => ['required', 'string', 'max:2000'],
            'trial_interest' => ['required', 'integer', 'between:1,5'],
            'recommended_class_id' => ['nullable', 'exists:class_rooms,id'],
        ], [], ['trial_result' => 'trial result', 'trial_interest' => 'child enthusiasm', 'recommended_class_id' => 'recommended class']);

        $first = $lead->status === 'trial_scheduled';
        $lead->update($data + ['status' => 'trial_done']);
        $lead->notes()->create(['user_id' => $user->id, 'type' => 'status', 'body' => "Trial result ({$data['trial_interest']}/5): {$data['trial_result']}"]);

        if ($first) {
            Notify::send(
                User::role(Role::Admin->value)->get(),
                "Trial result for {$lead->child_name} is in",
                'Time to offer a class to the parent.',
                route('leads.show', $lead),
                'clipboard-check',
                "lead-trial-done:{$lead->id}",
            );
        }

        return back()->with('success', 'Trial result saved.');
    }
}
