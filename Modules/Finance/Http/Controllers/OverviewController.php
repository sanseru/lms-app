<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\StudentPackage;

class OverviewController extends Controller
{
    public function __invoke(): Response
    {
        $monthStart = now()->startOfMonth();

        $months = collect(range(5, 0))->map(function (int $ago) {
            $start = now()->subMonths($ago)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return [
                'label' => $start->translatedFormat('M'),
                'billed' => (int) Invoice::where('status', '!=', 'cancelled')->whereBetween('due_date', [$start, $end])->sum('amount'),
                'received' => (int) Payment::where('status', 'verified')->whereBetween('paid_at', [$start, $end])->sum('amount'),
            ];
        });

        // Paket aktif yang sisa kuotanya ≤ 2 sesi atau kedaluwarsa dalam 14 hari: saatnya menagih perpanjangan.
        $lowQuota = StudentPackage::query()
            ->where('status', 'active')
            ->withCount('usages')
            ->with(['student:id,name', 'classRoom:id,name'])
            ->get()
            ->filter(fn (StudentPackage $p) => $p->remaining() <= 2 || ($p->expires_at && $p->expires_at->lte(today()->addDays(14))))
            ->sortBy(fn (StudentPackage $p) => [$p->remaining(), $p->expires_at?->timestamp ?? PHP_INT_MAX])
            ->take(10)
            ->values()
            ->map(fn (StudentPackage $p) => [
                ...$p->toSummary(),
                'student' => $p->student?->only('id', 'name'),
                'expiring' => $p->expires_at?->lte(today()->addDays(14)) ?? false,
            ]);

        return Inertia::render('Finance::Overview', [
            'lowQuota' => $lowQuota,
            'stats' => [
                'received_month' => (int) Payment::where('status', 'verified')->where('paid_at', '>=', $monthStart)->sum('amount'),
                'outstanding' => (int) Invoice::outstanding()->selectRaw('coalesce(sum(amount - paid_amount), 0) as total')->value('total'),
                'overdue_count' => Invoice::outstanding()->whereDate('due_date', '<', today())->count(),
                'pending_count' => Payment::where('status', 'pending')->count(),
            ],
            'months' => $months,
            'pending' => Payment::where('status', 'pending')
                ->with('invoice.student:id,name')
                ->latest()
                ->take(8)
                ->get()
                ->map(fn (Payment $p) => [
                    'id' => $p->id,
                    'invoice_id' => $p->invoice_id,
                    'invoice_number' => $p->invoice->number,
                    'student' => $p->invoice->student->name,
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->translatedFormat('d M Y'),
                    'bank_name' => $p->bank_name,
                ]),
        ]);
    }
}
