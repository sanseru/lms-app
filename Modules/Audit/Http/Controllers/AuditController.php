<?php

namespace Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Audit\Models\Activity;
use Modules\Audit\Support\AuditTrail;

class AuditController extends Controller
{
    /** Columns holding rupiah amounts, so the page can format them. */
    private const MONEY = ['amount', 'price', 'total', 'bonus', 'deduction', 'rate_per_session'];

    public function index(Request $request, AuditTrail $trail): Response
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:40'],
            'event' => ['nullable', 'string', 'max:20'],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $activities = Activity::query()
            ->with('causer:id,name')
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($filters['user'] ?? null, fn ($q, $v) => $q->where('causer_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->whereLike('subject_label', "%{$v}%"))
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'event' => $a->event,
                'event_label' => Activity::EVENTS[$a->event] ?? $a->event,
                'category' => $a->category,
                'label' => $a->subject_label,
                'causer' => $a->causer?->name,
                'ip' => $a->ip,
                'at' => $a->created_at->translatedFormat('d M Y H:i'),
                'ago' => $a->created_at->diffForHumans(),
                'changes' => collect($a->changes ?? [])->map(fn ($pair, $field) => [
                    'field' => $field,
                    'from' => $pair[0] ?? null,
                    'to' => $pair[1] ?? null,
                    'money' => in_array($field, self::MONEY, true),
                ])->values(),
            ]);

        return Inertia::render('Audit::Index', [
            'activities' => $activities,
            'filters' => $filters,
            'categories' => $trail->categories(),
            'events' => Activity::EVENTS,
            'users' => DB::table('users')
                ->whereIn('id', Activity::query()->whereNotNull('causer_id')->distinct()->select('causer_id'))
                ->orderBy('name')->get(['id', 'name']),
            'failedLogins' => Activity::where('event', 'login_failed')->where('created_at', '>=', now()->subDay())->count(),
        ]);
    }
}
