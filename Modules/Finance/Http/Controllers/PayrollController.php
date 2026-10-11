<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\PayrollItem;
use Modules\Finance\Models\TeacherPayout;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tutor fees: fee items are recorded automatically from completed sessions;
 * the admin bundles them per period into a pay slip, then marks it paid.
 */
class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        [$start, $end] = $this->period($request->input('month'));

        $unpaid = PayrollItem::query()
            ->whereNull('teacher_payout_id')
            ->whereBetween('session_date', [$start, $end])
            ->with('teacher:id,name')
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($items) => [
                'teacher' => ['id' => $items->first()->teacher->id, 'name' => $items->first()->teacher->name, 'initials' => $items->first()->teacher->initials()],
                'sessions_count' => $items->count(),
                'total' => (int) $items->sum('amount'),
            ])
            ->sortBy('teacher.name')
            ->values();

        $payouts = TeacherPayout::query()
            ->with('teacher:id,name')
            ->where('period_start', '<=', $end)->where('period_end', '>=', $start)
            ->latest()
            ->get()
            ->map(fn (TeacherPayout $p) => $this->payoutRow($p));

        return Inertia::render('Finance::Payroll/Index', [
            'month' => $start->format('Y-m'),
            'monthLabel' => $start->translatedFormat('F Y'),
            'unpaid' => $unpaid,
            'payouts' => $payouts,
            'totals' => [
                'unpaid' => (int) $unpaid->sum('total'),
                'payouts_unpaid' => (int) $payouts->where('status', 'unpaid')->sum('total'),
                'payouts_paid' => (int) $payouts->where('status', 'paid')->sum('total'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'month' => ['required', 'date_format:Y-m'],
            'bonus' => ['nullable', 'integer', 'min:0'],
            'deduction' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        [$start, $end] = $this->period($data['month']);

        $payout = DB::transaction(function () use ($data, $start, $end, $request) {
            $items = PayrollItem::where('teacher_id', $data['teacher_id'])
                ->whereNull('teacher_payout_id')
                ->whereBetween('session_date', [$start, $end])
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                return null;
            }

            $subtotal = (int) $items->sum('amount');
            $bonus = (int) ($data['bonus'] ?? 0);
            $deduction = min((int) ($data['deduction'] ?? 0), $subtotal + $bonus);

            $payout = TeacherPayout::create([
                'number' => TeacherPayout::nextNumber(),
                'teacher_id' => $data['teacher_id'],
                'period_start' => $start,
                'period_end' => $end,
                'sessions_count' => $items->count(),
                'subtotal' => $subtotal,
                'bonus' => $bonus,
                'deduction' => $deduction,
                'total' => $subtotal + $bonus - $deduction,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            PayrollItem::whereIn('id', $items->pluck('id'))->update(['teacher_payout_id' => $payout->id]);

            return $payout;
        });

        if (! $payout) {
            return back()->with('error', 'There are no unbundled sessions in this period.');
        }

        return to_route('finance.payroll.show', $payout)->with('success', 'Pay slip created.');
    }

    public function show(Request $request, TeacherPayout $payout): Response
    {
        abort_unless($request->user()->can('finance.payroll') || $payout->teacher_id === $request->user()->id, 403);
        $payout->load(['teacher.teacherProfile', 'items']);

        return Inertia::render('Finance::Payroll/Show', [
            'payout' => [
                ...$this->payoutRow($payout),
                'subtotal' => $payout->subtotal,
                'bonus' => $payout->bonus,
                'deduction' => $payout->deduction,
                'notes' => $payout->notes,
                'has_proof' => (bool) $payout->proof_path,
                'teacher' => [
                    'name' => $payout->teacher->name,
                    'code' => $payout->teacher->teacherProfile?->employee_code,
                    'bank_name' => $payout->teacher->teacherProfile?->bank_name,
                    'bank_account' => $payout->teacher->teacherProfile?->bank_account,
                ],
                'items' => $payout->items->map(fn (PayrollItem $i) => [
                    'id' => $i->id,
                    'date' => $i->session_date->translatedFormat('d M Y'),
                    'description' => $i->description,
                    'amount' => $i->amount,
                    'session_id' => $i->class_session_id,
                ]),
            ],
            'can' => ['manage' => $request->user()->can('finance.payroll')],
        ]);
    }

    public function markPaid(Request $request, TeacherPayout $payout): RedirectResponse
    {
        abort_unless($payout->status === 'unpaid', 422);

        $data = $request->validate([
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ], [], ['paid_at' => 'payment date', 'proof' => 'transfer proof']);

        $payout->update([
            'status' => 'paid',
            'paid_at' => $data['paid_at'],
            'proof_path' => $request->file('proof')?->store("payout-proofs/{$payout->id}", 'local'),
        ]);

        return back()->with('success', 'Fees marked as paid.');
    }

    public function destroy(TeacherPayout $payout): RedirectResponse
    {
        abort_unless($payout->status === 'unpaid', 422);

        DB::transaction(function () use ($payout) {
            $payout->items()->update(['teacher_payout_id' => null]);
            $payout->delete();
        });

        return to_route('finance.payroll.index', ['month' => $payout->period_start->format('Y-m')])->with('success', 'Pay slip deleted. Its sessions are back in the unbundled list.');
    }

    public function proof(Request $request, TeacherPayout $payout): StreamedResponse
    {
        abort_unless($request->user()->can('finance.payroll') || $payout->teacher_id === $request->user()->id, 403);
        abort_unless($payout->proof_path && Storage::disk('local')->exists($payout->proof_path), 404);

        return Storage::disk('local')->response($payout->proof_path);
    }

    /** Teacher page: their unbundled fees and their own pay slips. */
    public function mine(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Finance::Payroll/Mine', [
            'rate' => $user->teacherProfile?->rate_per_session ?? 0,
            'pending' => PayrollItem::where('teacher_id', $user->id)->whereNull('teacher_payout_id')->orderByDesc('session_date')->get()
                ->map(fn (PayrollItem $i) => ['id' => $i->id, 'date' => $i->session_date->translatedFormat('d M Y'), 'description' => $i->description, 'amount' => $i->amount]),
            'payouts' => TeacherPayout::where('teacher_id', $user->id)->latest()->get()->map(fn (TeacherPayout $p) => $this->payoutRow($p)),
        ]);
    }

    private function payoutRow(TeacherPayout $p): array
    {
        return [
            'id' => $p->id,
            'number' => $p->number,
            'teacher_name' => $p->teacher?->name,
            'period' => $p->period_start->translatedFormat('d M').' – '.$p->period_end->translatedFormat('d M Y'),
            'sessions_count' => $p->sessions_count,
            'total' => $p->total,
            'status' => $p->status,
            'status_label' => TeacherPayout::STATUSES[$p->status],
            'paid_at' => $p->paid_at?->translatedFormat('d M Y'),
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function period(?string $month): array
    {
        $start = $month && preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay()
            : now()->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }
}
