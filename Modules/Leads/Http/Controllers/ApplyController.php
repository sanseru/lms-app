<?php

namespace Modules\Leads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Enums\Role;
use Modules\Leads\Models\Lead;
use Modules\Notifications\Support\Notify;

/** Public "Book a free trial class" form on the website. */
class ApplyController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Leads::Apply', [
            'interests' => Lead::INTERESTS,
            'sources' => Lead::SOURCES,
            'interest' => $request->query('program'),
            'sent' => $request->session()->get('lead_sent'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Bot trap: a hidden field that humans never fill in.
        if ($request->filled('website')) {
            return redirect()->route('leads.apply')->with('lead_sent', $request->input('parent_name', 'friend'));
        }

        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{9,20}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'child_name' => ['required', 'string', 'max:120'],
            'child_age' => ['nullable', 'integer', 'between:4,18'],
            'child_grade' => ['nullable', 'string', 'max:30'],
            'interest' => ['nullable', Rule::in(Lead::INTERESTS)],
            'preferred_time' => ['nullable', 'string', 'max:150'],
            'source' => ['nullable', Rule::in(array_keys(Lead::SOURCES))],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [
            'phone.regex' => 'The WhatsApp number may only contain digits, spaces or a + sign.',
        ], [
            'parent_name' => 'parent name', 'phone' => 'WhatsApp number', 'child_name' => 'child name', 'child_age' => 'child age',
        ]);

        // Resubmitting the same form within a day does not create a duplicate lead.
        $lead = Lead::where('phone', $data['phone'])->where('child_name', $data['child_name'])
            ->where('created_at', '>=', now()->subDay())->first();

        if (! $lead) {
            $lead = Lead::create($data + ['status' => 'new']);
            $lead->notes()->create(['type' => 'status', 'body' => 'Signed up through the free trial form on the website.']);

            Notify::send(
                User::role(Role::Admin->value)->get(),
                "New lead: {$lead->child_name}",
                "{$lead->parent_name} · ".($lead->interest ?? 'no interest chosen').'. Contact them on WhatsApp as soon as possible.',
                route('leads.show', $lead),
                'user-plus',
                "lead-new:{$lead->id}",
            );
        }

        return redirect()->route('leads.apply')->with('lead_sent', $data['parent_name']);
    }
}
