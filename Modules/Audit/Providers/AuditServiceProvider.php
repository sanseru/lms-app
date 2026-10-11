<?php

namespace Modules\Audit\Providers;

use App\Models\User;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Audit\Support\AuditTrail;
use Modules\Certificates\Models\Certificate;
use Modules\Classes\Models\ClassRoom;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Package;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\StudentPackage;
use Modules\Finance\Models\TeacherPayout;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\People\Models\TeacherProfile;
use Modules\Sessions\Models\ClassSession;
use Modules\Sessions\Models\SessionAttendance;

/**
 * Audit log: who changed what and when, for data about money, grades,
 * attendance and accounts. Only admins can read it; nobody can change
 * or delete it.
 */
class AuditServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Audit';
    }

    public function permissions(): array
    {
        return ['audit.view' => ['admin']];
    }

    public function register(): void
    {
        $this->app->singleton(AuditTrail::class);
    }

    public function boot(): void
    {
        parent::boot();

        $trail = $this->app->make(AuditTrail::class);
        $this->watchModels($trail);

        Event::listen(Login::class, fn (Login $e) => $trail->log('login', 'Account', $e->user, $e->user->name, causerId: $e->user->getAuthIdentifier()));
        Event::listen(Failed::class, fn (Failed $e) => $trail->log('login_failed', 'Account', null, (string) ($e->credentials['login'] ?? $e->credentials['email'] ?? $e->credentials['username'] ?? '?'), causerId: null));

        // Demo data does not need to be logged.
        if ($this->app->runningConsoleCommand('db:seed', 'migrate:fresh', 'migrate:refresh')) {
            $trail->pause();
        }
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(new NavItem('Audit log', 'audit.index', 'shield-check', 'Other', 90, permission: 'audit.view'));
    }

    private function watchModels(AuditTrail $trail): void
    {
        $name = fn (?int $id) => $id ? (string) DB::table('users')->where('id', $id)->value('name') : '–';
        $invoiceNo = fn (?int $id) => $id ? (string) DB::table('invoices')->where('id', $id)->value('number') : '–';

        $trail->watch(User::class, 'Account', ['name', 'username', 'email', 'phone', 'status', 'password'], fn (User $u) => $u->name, values: ['status' => ['active' => 'Active', 'inactive' => 'Inactive']]);
        $trail->watch(TeacherProfile::class, 'Account', ['rate_per_session', 'bank_name', 'bank_account'], fn (TeacherProfile $t) => 'Teacher '.$name($t->user_id), ['updated']);

        $trail->watch(Invoice::class, 'Invoices', ['amount', 'status', 'due_date', 'title'], fn (Invoice $i) => "{$i->number} · {$name($i->student_id)}", values: ['status' => Invoice::STATUSES]);
        $trail->watch(Payment::class, 'Payments', ['amount', 'status', 'rejection_reason'], fn (Payment $p) => 'Rp '.number_format($p->amount, 0, ',', '.')." for {$invoiceNo($p->invoice_id)}", values: ['status' => Payment::STATUSES]);
        $trail->watch(Package::class, 'Session packages', ['name', 'price', 'sessions_count', 'valid_days', 'is_active'], fn (Package $p) => $p->name);
        $trail->watch(StudentPackage::class, 'Session packages', ['sessions_total', 'status', 'expires_at'], fn (StudentPackage $p) => "{$p->name} · {$name($p->student_id)}", ['updated', 'deleted'], ['status' => StudentPackage::STATUSES]);
        $trail->watch(TeacherPayout::class, 'Tutor fees', ['total', 'bonus', 'deduction', 'status'], fn (TeacherPayout $p) => "{$p->number} · {$name($p->teacher_id)}", values: ['status' => TeacherPayout::STATUSES]);

        $trail->watch(AssignmentSubmission::class, 'Grades', ['status', 'score'], fn (AssignmentSubmission $s) => 'Assignment '.DB::table('lessons')->where('id', $s->lesson_id)->value('title')." · {$name($s->student_id)}", ['updated'], ['status' => AssignmentSubmission::STATUSES]);
        $trail->watch(SessionAttendance::class, 'Attendance', ['status'], function (SessionAttendance $a) use ($name) {
            $at = DB::table('class_sessions')->where('id', $a->class_session_id)->value('starts_at');

            return $name($a->student_id).' · session '.($at ? Carbon::parse($at)->translatedFormat('d M Y H:i') : '?');
        }, ['updated'], ['status' => SessionAttendance::STATUSES]);
        $trail->watch(ClassSession::class, 'Schedule', ['status', 'starts_at', 'teacher_id', 'cancel_reason'], fn (ClassSession $s) => DB::table('class_rooms')->where('id', $s->class_room_id)->value('name').' · '.$s->starts_at?->translatedFormat('d M Y H:i'), ['updated'], ['status' => ClassSession::STATUSES, 'teacher_id' => $name]);
        $trail->watch(ClassRoom::class, 'Schedule', ['teacher_id', 'status', 'schedule_days', 'start_time', 'end_time'], fn (ClassRoom $c) => $c->name, ['updated', 'deleted'], ['status' => ClassRoom::STATUSES, 'teacher_id' => $name]);

        if (class_exists(Certificate::class)) {
            $trail->watch(Certificate::class, 'Certificates', ['title', 'revoked_at'], fn (Certificate $c) => "{$c->number} · {$name($c->student_id)}", ['created', 'updated']);
        }
    }
}
