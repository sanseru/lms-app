<?php

namespace Modules\Certificates\Providers;

use App\Models\User;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\DB;
use Modules\Gamification\Support\QueryRule;
use Modules\Gamification\Support\XpRules;

/**
 * Certificates module: teachers/admins issue certificates to students who
 * finish a course. Each certificate has a number, a public verification code
 * (/certificates/{code}) and a QR code, and can be printed or saved as PDF.
 */
class CertificatesServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Certificates';
    }

    public function permissions(): array
    {
        return ['certificates.issue' => ['admin', 'teacher']];
    }

    public function boot(): void
    {
        parent::boot();

        // Example of a module adding an XP rule to the Gamification module.
        $this->callAfterResolving(XpRules::class, fn (XpRules $rules) => $rules->add(
            new QueryRule('certificate', 'Earned a certificate', 100, fn (User $u) => DB::table('certificates')
                ->where('student_id', $u->id)->whereNull('revoked_at')->select('id as source_id', 'issued_at as at')),
        ));
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Certificates', 'certificates.index', 'award', 'Learning', 37, roles: ['admin', 'teacher'], active: 'certificates.*'),
            new NavItem('My certificates', 'certificates.index', 'award', 'Learning', 37, roles: ['student'], active: 'certificates.*'),
            new NavItem("Child's certificates", 'certificates.index', 'award', 'Learning', 37, roles: ['guardian'], active: 'certificates.*'),
        );
    }
}
