<?php

namespace Modules\Leads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Identity\Enums\Role;
use Modules\Leads\Actions\ConvertLead;
use Modules\Leads\Models\Lead;
use Modules\Leads\Models\LeadNote;
use Modules\Notifications\Support\Notify;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $filter = fn ($q) => $q->when($search, fn ($q, $s) => $q->where(fn ($q) => $q
            ->where('parent_name', 'like', "%{$s}%")->orWhere('child_name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")));

        $open = Lead::whereIn('status', Lead::OPEN)->tap($filter)->with('trialTeacher:id,name')
            ->orderByRaw('trial_at is null, trial_at')->oldest()->get()->groupBy('status');

        $closed = Lead::whereIn('status', ['won', 'lost'])->tap($filter)->with('trialTeacher:id,name')
            ->latest('closed_at')->take(30)->get();

        $since = now()->subDays(90);
        $won = Lead::where('status', 'won')->where('closed_at', '>=', $since)->count();
        $lost = Lead::where('status', 'lost')->where('closed_at', '>=', $since)->count();

        return Inertia::render('Leads::Index', [
            'columns' => collect(Lead::OPEN)->map(fn ($status) => [
                'status' => $status,
                'label' => Lead::STATUSES[$status],
                'leads' => $open->get($status, collect())->map->toCard()->values(),
            ]),
            'closed' => $closed->map(fn (Lead $l) => $l->toCard() + ['lost_reason' => $l->lost_reason, 'closed' => $l->closed_at?->translatedFormat('d M Y')]),
            'stats' => [
                'new_month' => Lead::where('created_at', '>=', now()->startOfMonth())->count(),
                'trials_week' => Lead::where('status', 'trial_scheduled')->whereBetween('trial_at', [now()->startOfDay(), now()->addDays(7)->endOfDay()])->count(),
                'waiting' => Lead::where('status', 'new')->where('created_at', '<=', now()->subDay())->count(),
                'conversion' => $won + $lost ? (int) round($won / ($won + $lost) * 100) : null,
                'won_90' => $won,
                'sources' => Lead::where('created_at', '>=', $since)->whereNotNull('source')->selectRaw('source, count(*) as total')
                    ->groupBy('source')->orderByDesc('total')->get()
                    ->map(fn ($r) => ['label' => Lead::SOURCES[$r->source] ?? $r->source, 'total' => (int) $r->total]),
            ],
            'search' => $search,
            'interests' => Lead::INTERESTS,
            'sources' => Lead::SOURCES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $lead = Lead::create($this->validateLead($request) + ['status' => 'new']);
        $lead->notes()->create(['user_id' => $request->user()->id, 'type' => 'status', 'body' => 'Dicatat manual oleh admin.']);

        return redirect()->route('leads.show', $lead)->with('success', 'Calon siswa ditambahkan.');
    }

    public function show(Lead $lead, ConvertLead $convert): Response
    {
        $lead->load(['notes.user:id,name', 'trialTeacher:id,name', 'recommendedClass:id,name', 'student:id,name,username']);
        $guardian = $lead->isOpen() ? $convert->existingGuardian($lead) : null;

        return Inertia::render('Leads::Show', [
            'lead' => [
                ...$lead->only('id', 'parent_name', 'phone', 'email', 'child_name', 'child_age', 'child_grade', 'interest', 'preferred_time', 'source', 'message', 'status', 'lost_reason', 'trial_zoom_url', 'trial_result', 'trial_interest', 'trial_teacher_id', 'recommended_class_id'),
                'status_label' => Lead::STATUSES[$lead->status],
                'source_label' => Lead::SOURCES[$lead->source] ?? null,
                'wa' => $lead->waNumber(),
                'trial_at' => $lead->trial_at?->format('Y-m-d\TH:i'),
                'trial_at_label' => $lead->trial_at?->translatedFormat('l, d M Y H:i'),
                'trial_teacher' => $lead->trialTeacher?->name,
                'recommended_class' => $lead->recommendedClass?->name,
                'student' => $lead->student?->only('id', 'name', 'username'),
                'created' => $lead->created_at->translatedFormat('d M Y H:i'),
                'notes' => $lead->notes->map(fn (LeadNote $n) => [
                    'id' => $n->id, 'type' => $n->type, 'body' => $n->body, 'user' => $n->user?->name, 'at' => $n->created_at->translatedFormat('d M Y H:i'),
                ]),
            ],
            'statuses' => Lead::STATUSES,
            'interests' => Lead::INTERESTS,
            'sources' => Lead::SOURCES,
            'teachers' => User::role(Role::Teacher->value)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'classes' => ClassRoom::whereIn('status', ['active', 'upcoming'])->orderBy('name')->get(['id', 'name', 'subject']),
            'existingGuardian' => $guardian?->only('id', 'name', 'username'),
            'suggested' => [
                'student' => $this->suggestUsername($lead->child_name),
                'guardian' => $this->suggestUsername($lead->parent_name),
            ],
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $lead->update($this->validateLead($request));

        return back()->with('success', 'Data calon siswa diperbarui.');
    }

    public function note(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => 'catatan']);
        $lead->notes()->create(['user_id' => $request->user()->id, 'type' => 'note', 'body' => $data['body']]);

        return back()->with('success', 'Catatan ditambahkan.');
    }

    public function contacted(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->status === 'new', 422);
        $lead->update(['status' => 'contacted', 'contacted_at' => now()]);
        $this->log($request, $lead, 'Sudah dihubungi.');

        return back()->with('success', 'Ditandai sudah dihubungi.');
    }

    public function schedule(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless(in_array($lead->status, ['new', 'contacted', 'trial_scheduled'], true), 422);

        $data = $request->validate([
            'trial_at' => ['required', 'date', 'after:now'],
            'trial_teacher_id' => ['required', 'integer', function ($attribute, $value, $fail) {
                if (! User::find($value)?->hasRole(Role::Teacher->value)) {
                    $fail('Pilih guru yang terdaftar.');
                }
            }],
            'trial_zoom_url' => ['nullable', 'url', 'max:255'],
        ], [], ['trial_at' => 'waktu trial', 'trial_teacher_id' => 'guru', 'trial_zoom_url' => 'tautan Zoom']);

        $rescheduled = $lead->status === 'trial_scheduled';
        $lead->update($data + ['status' => 'trial_scheduled', 'contacted_at' => $lead->contacted_at ?? now()]);
        $lead->load('trialTeacher');
        $when = $lead->trial_at->translatedFormat('l, d M Y H:i');
        $this->log($request, $lead, ($rescheduled ? 'Trial dipindah ke ' : 'Trial dijadwalkan ')."{$when} bersama {$lead->trialTeacher->name}.");

        Notify::send(
            $lead->trialTeacher,
            "Kelas trial: {$lead->child_name}",
            "{$when}".($lead->interest ? " · {$lead->interest}" : '').'. Catat hasilnya setelah trial selesai.',
            route('leads.trials'),
            'calendar-days',
            "lead-trial:{$lead->id}:{$lead->trial_at->timestamp}",
        );

        return back()->with('success', 'Trial dijadwalkan dan guru diberi tahu.');
    }

    public function lose(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->isOpen(), 422);
        $data = $request->validate(['lost_reason' => ['required', 'string', 'max:255']], [], ['lost_reason' => 'alasan']);
        $lead->update(['status' => 'lost', 'lost_reason' => $data['lost_reason'], 'closed_at' => now()]);
        $this->log($request, $lead, "Tidak lanjut: {$data['lost_reason']}");

        return back()->with('success', 'Ditandai tidak lanjut.');
    }

    public function reopen(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->status === 'lost', 422);
        $lead->update(['status' => $lead->trial_result ? 'trial_done' : ($lead->contacted_at ? 'contacted' : 'new'), 'lost_reason' => null, 'closed_at' => null]);
        $this->log($request, $lead, 'Dibuka kembali.');

        return back()->with('success', 'Calon siswa dibuka kembali.');
    }

    public function convert(Request $request, Lead $lead, ConvertLead $convert): RedirectResponse
    {
        abort_unless($lead->isOpen(), 422);
        $existing = $convert->existingGuardian($lead);

        $data = $request->validate([
            'student_username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:40', 'unique:users,username'],
            'guardian_username' => [$existing ? 'nullable' : 'required', 'string', 'alpha_dash', 'min:3', 'max:40', 'unique:users,username', 'different:student_username'],
            'password' => ['required', 'string', 'min:8'],
            'relationship' => ['required', Rule::in(['ayah', 'ibu', 'wali'])],
            'class_room_id' => ['nullable', 'exists:class_rooms,id'],
        ], [], ['student_username' => 'username anak', 'guardian_username' => 'username orang tua', 'password' => 'kata sandi', 'class_room_id' => 'kelas']);

        $student = $convert->handle($lead, [
            ...$data,
            'student_username' => strtolower($data['student_username']),
            'guardian_username' => isset($data['guardian_username']) ? strtolower($data['guardian_username']) : null,
        ], $request->user());

        return redirect()->route('leads.show', $lead)->with('success', "{$student->name} sekarang terdaftar sebagai siswa. Kirim username dan kata sandi ke orang tua.");
    }

    private function log(Request $request, Lead $lead, string $body): void
    {
        $lead->notes()->create(['user_id' => $request->user()->id, 'type' => 'status', 'body' => $body]);
    }

    private function validateLead(Request $request): array
    {
        return $request->validate([
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
        ], ['phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, atau tanda +.'], [
            'parent_name' => 'nama orang tua', 'phone' => 'nomor WhatsApp', 'child_name' => 'nama anak', 'child_age' => 'usia anak',
        ]);
    }

    private function suggestUsername(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', explode(' ', trim($name))[0] ?? '')) ?: 'siswa';
        $candidate = $base;
        for ($i = 2; User::where('username', $candidate)->exists(); $i++) {
            $candidate = $base.$i;
        }

        return $candidate;
    }
}
