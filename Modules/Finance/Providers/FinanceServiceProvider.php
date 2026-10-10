<?php

namespace Modules\Finance\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Gate;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Policies\InvoicePolicy;

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
        ];
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Invoice::class, InvoicePolicy::class);
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Ringkasan Keuangan', 'finance.overview', 'wallet', 'Keuangan', 10, permission: 'finance.manage'),
            new NavItem('Tagihan', 'finance.invoices.index', 'receipt', 'Keuangan', 20, permission: 'finance.manage', active: 'finance.invoices.*'),
            new NavItem('Tagihan Anak', 'finance.invoices.index', 'receipt', 'Keuangan', 20, roles: ['guardian'], active: 'finance.invoices.*'),
        );
    }
}
