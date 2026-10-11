<?php

namespace Modules\Leads\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Modules\Audit\Support\AuditTrail;
use Modules\Leads\Models\Lead;

/**
 * Calon siswa: formulir coba gratis di website, pipeline admin dari baru
 * masuk sampai jadi siswa, jadwal kelas trial, dan catatan hasil trial guru.
 */
class LeadsServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Leads';
    }

    public function permissions(): array
    {
        return [
            'leads.manage' => ['admin'],
            'leads.trial' => ['admin', 'teacher'],
        ];
    }

    public function boot(): void
    {
        parent::boot();

        if (class_exists(AuditTrail::class) && $this->app->bound(AuditTrail::class)) {
            $this->callAfterResolving(AuditTrail::class, fn (AuditTrail $trail) => $trail->watch(
                Lead::class, 'Calon siswa', ['status', 'trial_at', 'trial_teacher_id', 'lost_reason'],
                fn (Lead $l) => "{$l->child_name} · {$l->parent_name}", ['updated', 'deleted'], ['status' => Lead::STATUSES],
            ));
        }
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Calon Siswa', 'leads.index', 'user-plus', 'Pengguna', 5, permission: 'leads.manage', active: 'leads.*'),
            new NavItem('Kelas Trial', 'leads.trials', 'calendar-days', 'Akademik', 45, roles: ['teacher'], active: 'leads.trials'),
        );
    }
}
