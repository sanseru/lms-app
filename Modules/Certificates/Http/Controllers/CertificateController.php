<?php

namespace Modules\Certificates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Certificates\Actions\EligibleStudents;
use Modules\Certificates\Actions\IssueCertificate;
use Modules\Certificates\Http\Requests\CertificateRequest;
use Modules\Certificates\Models\Certificate;
use Modules\Classes\Models\ClassRoom;

class CertificateController extends Controller
{
    public function index(Request $request, EligibleStudents $eligible): Response
    {
        $user = $request->user();
        $certificates = Certificate::query()->visibleTo($user)->with(['student', 'classRoom', 'course'])
            ->latest('issued_at')->latest('id')->get()->map->toItem();

        if (! $user->can('certificates.issue')) {
            return Inertia::render('Certificates::Index', ['mode' => $user->hasRole('guardian') ? 'guardian' : 'student', 'certificates' => $certificates]);
        }

        $classes = $this->classesFor($user)->load(['course', 'students' => fn ($q) => $q->wherePivot('status', 'active')->orderBy('name')]);

        return Inertia::render('Certificates::Index', [
            'mode' => 'staff',
            'certificates' => $certificates,
            'eligible' => $eligible->for($classes),
            'classes' => $classes->map(fn (ClassRoom $c) => [
                'id' => $c->id, 'name' => $c->name, 'course_id' => $c->course_id, 'course_title' => $c->course?->title,
                'students' => $c->students->map(fn (User $s) => ['id' => $s->id, 'name' => $s->name])->values(),
            ])->values(),
        ]);
    }

    public function store(CertificateRequest $request, IssueCertificate $issue): RedirectResponse
    {
        $certificate = $issue->handle(User::findOrFail($request->validated('student_id')), $request->validated(), $request->user());

        return back()->with('success', "Certificate {$certificate->number} issued to {$certificate->student->name}.");
    }

    public function show(Request $request, Certificate $certificate): Response
    {
        abort_unless(Certificate::query()->visibleTo($request->user())->whereKey($certificate->id)->exists(), 403);
        $certificate->load(['student', 'classRoom', 'course']);

        return Inertia::render('Certificates::Show', [
            'certificate' => $certificate->toItem(),
            'canRevoke' => $this->canManage($request->user(), $certificate),
        ]);
    }

    public function revoke(Request $request, Certificate $certificate): RedirectResponse
    {
        abort_unless($this->canManage($request->user(), $certificate), 403);
        $certificate->update(['revoked_at' => $certificate->revoked_at ? null : now()]);

        return back()->with('success', $certificate->revoked_at ? 'Certificate revoked. The verification page now shows it as no longer valid.' : 'Certificate is valid again.');
    }

    /** Public page to check a certificate is genuine from its code/QR. */
    public function verify(string $code): Response
    {
        $certificate = Certificate::with('student')->where('code', strtoupper($code))->first();

        return Inertia::render('Certificates::Verify', [
            'code' => strtoupper($code),
            'certificate' => $certificate ? [
                'number' => $certificate->number,
                // Last names are shortened to protect children's data.
                'student' => collect(explode(' ', $certificate->student->name))->map(fn ($p, $i) => $i === 0 ? $p : mb_substr($p, 0, 1).'.')->implode(' '),
                'title' => $certificate->title,
                'issued_at' => $certificate->issued_at->translatedFormat('d F Y'),
                'valid' => $certificate->revoked_at === null,
            ] : null,
        ]);
    }

    private function canManage(User $user, Certificate $certificate): bool
    {
        return $user->hasRole('admin') || ($user->can('certificates.issue') && $certificate->classRoom?->teacher_id === $user->id);
    }

    private function classesFor(User $user)
    {
        return ClassRoom::query()->when(! $user->hasRole('admin'), fn ($q) => $q->where('teacher_id', $user->id))->orderBy('name')->get();
    }
}
