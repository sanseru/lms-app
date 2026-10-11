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

/** Formulir publik "Coba kelas gratis" di website. */
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
        // Jebakan bot: kolom tersembunyi yang tidak pernah diisi manusia.
        if ($request->filled('website')) {
            return redirect()->route('leads.apply')->with('lead_sent', $request->input('parent_name', 'Bapak/Ibu'));
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
            'phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, atau tanda +.',
        ], [
            'parent_name' => 'nama orang tua', 'phone' => 'nomor WhatsApp', 'child_name' => 'nama anak', 'child_age' => 'usia anak',
        ]);

        // Kirim ulang formulir yang sama dalam sehari tidak membuat calon siswa ganda.
        $lead = Lead::where('phone', $data['phone'])->where('child_name', $data['child_name'])
            ->where('created_at', '>=', now()->subDay())->first();

        if (! $lead) {
            $lead = Lead::create($data + ['status' => 'new']);
            $lead->notes()->create(['type' => 'status', 'body' => 'Mendaftar lewat formulir coba gratis di website.']);

            Notify::send(
                User::role(Role::Admin->value)->get(),
                "Calon siswa baru: {$lead->child_name}",
                "{$lead->parent_name} · ".($lead->interest ?? 'minat belum dipilih').'. Hubungi lewat WhatsApp secepatnya.',
                route('leads.show', $lead),
                'user-plus',
                "lead-new:{$lead->id}",
            );
        }

        return redirect()->route('leads.apply')->with('lead_sent', $data['parent_name']);
    }
}
