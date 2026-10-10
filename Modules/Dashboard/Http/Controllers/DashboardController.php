<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Identity\Enums\Role;

/**
 * Satu URL /dashboard, tampilan berbeda per role utama user.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return match ($user->primaryRole()) {
            Role::Admin => $this->admin(),
            Role::Teacher => $this->teacher($user),
            Role::Guardian => $this->guardian($user),
            Role::Student => $this->student($user),
            default => Inertia::render('Dashboard::NoRole'),
        };
    }

    private function admin(): Response
    {
        $today = ClassRoom::onDay(today())->with('teacher:id,name')->withCount('students')->orderBy('start_time')->get();

        return Inertia::render('Dashboard::Admin', [
            'stats' => [
                'students' => User::role(Role::Student->value)->where('status', 'active')->count(),
                'teachers' => User::role(Role::Teacher->value)->where('status', 'active')->count(),
                'classes' => ClassRoom::where('status', 'active')->count(),
                'outstanding' => (int) Invoice::outstanding()->selectRaw('coalesce(sum(amount - paid_amount), 0) as total')->value('total'),
            ],
            'today' => $today->map->toCard(),
            'pendingPayments' => Payment::where('status', 'pending')->with('invoice.student:id,name')->latest()->take(5)->get()
                ->map(fn (Payment $p) => [
                    'id' => $p->id,
                    'invoice_id' => $p->invoice_id,
                    'student' => $p->invoice->student->name,
                    'amount' => $p->amount,
                    'paid_at' => $p->paid_at->translatedFormat('d M'),
                ]),
            'recentStudents' => User::role(Role::Student->value)->with('studentProfile:id,user_id,student_code')->latest()->take(5)->get()
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'initials' => $u->initials(), 'code' => $u->studentProfile?->student_code, 'joined' => $u->created_at->diffForHumans()]),
        ]);
    }

    private function teacher(User $user): Response
    {
        $classes = $user->teachingClasses()->withCount('students')->orderBy('start_time')->get();

        return Inertia::render('Dashboard::Teacher', [
            'stats' => [
                'classes' => $classes->where('status', 'active')->count(),
                'students' => $classes->where('status', 'active')->sum('students_count'),
                'sessions_week' => $classes->where('status', 'active')->sum(fn ($c) => count($c->schedule_days)),
            ],
            'today' => ClassRoom::onDay(today())->where('teacher_id', $user->id)->withCount('students')->orderBy('start_time')->get()->map->toCard(),
            'classes' => $classes->map->toCard(),
        ]);
    }

    private function student(User $user): Response
    {
        $classes = $user->enrolledClasses()->with('teacher:id,name')->get();

        return Inertia::render('Dashboard::Student', [
            'today' => ClassRoom::onDay(today())->whereHas('students', fn ($q) => $q->whereKey($user->id))->with('teacher:id,name')->orderBy('start_time')->get()->map->toCard(),
            'classes' => $classes->map->toCard(),
            'profile' => [
                'code' => $user->studentProfile?->student_code,
                'grade' => $user->studentProfile?->grade,
            ],
        ]);
    }

    private function guardian(User $user): Response
    {
        $children = $user->children()->with(['studentProfile', 'enrolledClasses.teacher:id,name'])->get();

        return Inertia::render('Dashboard::Guardian', [
            'children' => $children->map(fn (User $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'initials' => $child->initials(),
                'code' => $child->studentProfile?->student_code,
                'grade' => $child->studentProfile?->grade,
                'classes' => $child->enrolledClasses->map->toCard(),
            ]),
            'invoices' => Invoice::visibleTo($user)->outstanding()->with('student:id,name')->orderBy('due_date')->take(5)->get()
                ->map(fn (Invoice $i) => [
                    'id' => $i->id,
                    'number' => $i->number,
                    'title' => $i->title,
                    'student' => $i->student->name,
                    'remaining' => $i->remaining(),
                    'due_date' => $i->due_date->translatedFormat('d M Y'),
                    'is_overdue' => $i->isOverdue(),
                ]),
        ]);
    }
}
