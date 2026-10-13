<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Finance\Http\Requests\InvoiceRequest;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Support\AmountInWords;
use Modules\Identity\Enums\Role;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can('finance.manage') || $user->can('finance.pay'), 403);
        $filters = $request->only('search', 'status');

        $invoices = Invoice::query()
            ->visibleTo($user)
            ->with(['student:id,name', 'student.studentProfile:id,user_id,student_code'])
            ->withCount(['payments as pending_payments_count' => fn ($q) => $q->where('status', 'pending')])
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->whereLike('number', "%{$search}%")
                ->orWhereLike('title', "%{$search}%")
                ->orWhereHas('student', fn ($q) => $q->whereLike('name', "%{$search}%"))))
            ->when($filters['status'] ?? null, function ($q, $status) {
                return match ($status) {
                    'overdue' => $q->outstanding()->whereDate('due_date', '<', today()),
                    'pending' => $q->whereHas('payments', fn ($q) => $q->where('status', 'pending')),
                    default => $q->where('status', $status),
                };
            })
            ->orderByRaw("case status when 'unpaid' then 0 when 'partial' then 1 when 'paid' then 2 else 3 end")
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => $this->row($invoice));

        return Inertia::render('Finance::Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'statuses' => Invoice::STATUSES,
            'can' => ['manage' => $user->can('finance.manage')],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Finance::Invoices/Create', [
            'students' => User::role(Role::Student->value)->where('status', 'active')->with('studentProfile:id,user_id,student_code')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->studentProfile?->student_code]),
            'classes' => ClassRoom::query()->whereIn('status', ['active', 'upcoming'])->withCount('students')->orderBy('name')->get(['id', 'name', 'code'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'students_count' => $c->students_count]),
        ]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $studentIds = $data['target'] === 'class'
            ? ClassRoom::findOrFail($data['class_room_id'])->students()->wherePivot('status', 'active')->pluck('users.id')
            : collect([$data['student_id']]);

        if ($studentIds->isEmpty()) {
            return back()->with('error', 'This class has no active students yet.');
        }

        DB::transaction(function () use ($studentIds, $data, $request) {
            foreach ($studentIds as $studentId) {
                Invoice::create([
                    'number' => Invoice::nextNumber(),
                    'student_id' => $studentId,
                    'class_room_id' => $data['class_room_id'] ?? null,
                    'title' => $data['title'],
                    'period' => isset($data['period']) ? Carbon::createFromFormat('Y-m', $data['period'])->startOfMonth() : null,
                    'amount' => $data['amount'],
                    'due_date' => $data['due_date'],
                    'notes' => $data['notes'] ?? null,
                    'issued_by' => $request->user()->id,
                ]);
            }
        });

        return to_route('finance.invoices.index')->with('success', "{$studentIds->count()} invoice(s) created.");
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        Gate::authorize('view', $invoice);
        $invoice->load(['student.studentProfile', 'student.guardians:id,name,phone', 'classRoom:id,name', 'payments.submitter:id,name', 'payments.verifier:id,name']);

        return Inertia::render('Finance::Invoices/Show', [
            'invoice' => [
                ...$this->row($invoice),
                'notes' => $invoice->notes,
                'class' => $invoice->classRoom?->only('id', 'name'),
                'guardians' => $invoice->student->guardians->map->only('name', 'phone'),
                'payments' => $invoice->payments->map(fn (Payment $p) => [
                    'id' => $p->id,
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->translatedFormat('d M Y'),
                    'method' => $p->method,
                    'bank_name' => $p->bank_name,
                    'account_name' => $p->account_name,
                    'status' => $p->status,
                    'status_label' => Payment::STATUSES[$p->status],
                    'notes' => $p->notes,
                    'rejection_reason' => $p->rejection_reason,
                    'has_proof' => (bool) $p->proof_path,
                    'submitted_by' => $p->submitter?->name,
                    'verified_by' => $p->verifier?->name,
                    'verified_at' => $p->verified_at?->translatedFormat('d M Y H:i'),
                ]),
            ],
            'bank' => config('finance.bank'),
            'can' => [
                'receipt' => $invoice->status !== 'cancelled' && $invoice->paid_amount > 0,
                'manage' => $request->user()->can('finance.manage'),
                'pay' => $request->user()->can('pay', $invoice) && in_array($invoice->status, ['unpaid', 'partial']),
            ],
        ]);
    }

    /** Receipt for verified payments (ready to print / save as PDF). */
    public function receipt(Invoice $invoice): Response
    {
        Gate::authorize('view', $invoice);
        abort_if($invoice->status === 'cancelled' || $invoice->paid_amount <= 0, 404);

        $invoice->load(['student.studentProfile', 'student.guardians:id,name', 'classRoom:id,name']);
        $payments = $invoice->payments()->where('status', 'verified')->with('verifier:id,name')->orderBy('paid_at')->get();

        return Inertia::render('Finance::Invoices/Receipt', [
            'receipt' => [
                'number' => 'RCP-'.$invoice->number,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'title' => $invoice->title,
                'period' => $invoice->period?->translatedFormat('F Y'),
                'class' => $invoice->classRoom?->name,
                'student' => $invoice->student->name,
                'student_code' => $invoice->student->studentProfile?->student_code,
                'payer' => $invoice->student->guardians->pluck('name')->implode(' / ') ?: $invoice->student->name,
                'amount' => $invoice->amount,
                'paid_amount' => $invoice->paid_amount,
                'remaining' => $invoice->remaining(),
                'paid_in_full' => $invoice->status === 'paid',
                'in_words' => AmountInWords::rupiah($invoice->paid_amount),
                'date' => ($payments->last()?->verified_at ?? now())->translatedFormat('d F Y'),
                'verifier' => $payments->last()?->verifier?->name,
                'payments' => $payments->map(fn (Payment $p) => [
                    'id' => $p->id,
                    'paid_at' => $p->paid_at->translatedFormat('d M Y'),
                    'method' => $p->method === 'cash' ? 'Cash' : trim('Transfer '.$p->bank_name),
                    'amount' => $p->amount,
                ]),
            ],
            'company' => config('finance.bank.account_name'),
        ]);
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        if ($invoice->paid_amount > 0) {
            return back()->with('error', 'An invoice that already has payments cannot be cancelled.');
        }

        $invoice->update(['status' => 'cancelled']);

        return back()->with('success', 'Invoice cancelled.');
    }

    private function row(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'title' => $invoice->title,
            'period' => $invoice->period?->translatedFormat('F Y'),
            'amount' => $invoice->amount,
            'paid_amount' => $invoice->paid_amount,
            'remaining' => $invoice->remaining(),
            'due_date' => $invoice->due_date->translatedFormat('d M Y'),
            'status' => $invoice->status,
            'status_label' => Invoice::STATUSES[$invoice->status],
            'is_overdue' => $invoice->isOverdue(),
            'pending_payments' => $invoice->pending_payments_count ?? $invoice->payments->where('status', 'pending')->count(),
            'student' => [
                'id' => $invoice->student->id,
                'name' => $invoice->student->name,
                'initials' => $invoice->student->initials(),
                'code' => $invoice->student->studentProfile?->student_code,
            ],
        ];
    }
}
