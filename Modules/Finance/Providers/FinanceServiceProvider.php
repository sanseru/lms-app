<?php

namespace Modules\Finance\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Finance\Listeners\ApplySessionToQuota;
use Modules\Finance\Listeners\RecordTutorFee;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Policies\InvoicePolicy;
use Modules\Sessions\Events\SessionCompleted;

class FinanceServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Finance';
    }

    public function permissions(): array
    {
        return [
            'finance.manage' => ['admin'],
            'finance.pay' => ['guardian'],
            'finance.payroll' => ['admin'],
        ];
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Invoice::class, InvoicePolicy::class);

        Event::listen(SessionCompleted::class, ApplySessionToQuota::class);
        Event::listen(SessionCompleted::class, RecordTutorFee::class);
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Ringkasan Keuangan', 'finance.overview', 'wallet', 'Keuangan', 10, permission: 'finance.manage'),
            new NavItem('Tagihan', 'finance.invoices.index', 'receipt', 'Keuangan', 20, permission: 'finance.manage', active: 'finance.invoices.*'),
            new NavItem('Paket Kuota', 'finance.packages.index', 'ticket', 'Keuangan', 30, permission: 'finance.manage', active: 'finance.packages.*'),
            new NavItem('Honor Tutor', 'finance.payroll.index', 'hand-coins', 'Keuangan', 40, permission: 'finance.payroll', active: 'finance.payroll.*'),
            new NavItem('Honor Saya', 'finance.my-fees', 'hand-coins', 'Keuangan', 40, roles: ['teacher']),
            new NavItem('Tagihan Anak', 'finance.invoices.index', 'receipt', 'Keuangan', 20, roles: ['guardian'], active: 'finance.invoices.*'),
        );
    }
}
