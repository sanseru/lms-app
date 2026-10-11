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
 * Modul Sertifikat: guru/admin menerbitkan sertifikat untuk siswa yang
 * menyelesaikan kursus. Setiap sertifikat punya nomor, kode verifikasi publik
 * (/sertifikat/{kode}) dan QR, serta bisa dicetak atau disimpan sebagai PDF.
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

        // Contoh modul yang menambah aturan XP ke modul Gamifikasi.
        $this->callAfterResolving(XpRules::class, fn (XpRules $rules) => $rules->add(
            new QueryRule('certificate', 'Mendapat sertifikat', 100, fn (User $u) => DB::table('certificates')
                ->where('student_id', $u->id)->whereNull('revoked_at')->select('id as source_id', 'issued_at as at')),
        ));
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Sertifikat', 'certificates.index', 'award', 'Belajar', 37, roles: ['admin', 'teacher'], active: 'certificates.*'),
            new NavItem('Sertifikatku', 'certificates.index', 'award', 'Belajar', 37, roles: ['student'], active: 'certificates.*'),
            new NavItem('Sertifikat Anak', 'certificates.index', 'award', 'Belajar', 37, roles: ['guardian'], active: 'certificates.*'),
        );
    }
}
