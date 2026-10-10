<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Package;
use Modules\Finance\Models\StudentPackage;
use Modules\Identity\Enums\Role;

class PackageController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'status');

        $studentPackages = StudentPackage::query()
            ->with(['student:id,name', 'classRoom:id,name'])
            ->withCount('usages')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $s) => $s === 'low'
                ? $q->where('status', 'active')->whereRaw('sessions_total - (select count(*) from session_attendances where student_package_id = student_packages.id) <= 2')
                : $q->where('status', $s))
            ->orderByRaw("case status when 'active' then 0 when 'pending_payment' then 1 else 2 end")
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (StudentPackage $p) => [...$p->toSummary(), 'student' => ['id' => $p->student->id, 'name' => $p->student->name, 'initials' => $p->student->initials()]]);

        return Inertia::render('Finance::Packages/Index', [
            'packages' => Package::orderBy('sessions_count')->get(),
            'studentPackages' => $studentPackages,
            'filters' => $filters,
            'students' => User::role(Role::Student->value)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'classes' => ClassRoom::whereIn('status', ['active', 'upcoming'])->orderBy('name')->get(['id', 'name']),
            'statuses' => StudentPackage::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Package::create($this->validatePackage($request));

        return back()->with('success', 'Paket ditambahkan.');
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $package->update($this->validatePackage($request));

        return back()->with('success', 'Paket diperbarui.');
    }

    /** Jual paket ke siswa: buat tagihan, paket aktif setelah lunas (atau langsung bila gratis/trial). */
    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', Rule::exists('packages', 'id')->where('is_active', true)],
            'student_id' => ['required', Rule::exists('student_profiles', 'user_id')],
            'class_room_id' => ['nullable', Rule::exists('class_rooms', 'id')],
            'due_date' => ['required', 'date'],
            'activate_now' => ['boolean'],
        ], [], ['package_id' => 'paket', 'student_id' => 'siswa', 'due_date' => 'jatuh tempo']);

        $package = Package::findOrFail($data['package_id']);

        DB::transaction(function () use ($data, $package, $request) {
            $invoice = $package->price > 0 ? Invoice::create([
                'number' => Invoice::nextNumber(),
                'student_id' => $data['student_id'],
                'class_room_id' => $data['class_room_id'] ?? null,
                'title' => "Paket {$package->name}",
                'amount' => $package->price,
                'due_date' => $data['due_date'],
                'issued_by' => $request->user()->id,
            ]) : null;

            $studentPackage = StudentPackage::create([
                'student_id' => $data['student_id'],
                'package_id' => $package->id,
                'class_room_id' => $data['class_room_id'] ?? null,
                'invoice_id' => $invoice?->id,
                'name' => $package->name,
                'sessions_total' => $package->sessions_count,
                'price' => $package->price,
                'valid_days' => $package->valid_days,
            ]);

            if (! $invoice || ($data['activate_now'] ?? false)) {
                $studentPackage->activate();
            }
        });

        return back()->with('success', $package->price > 0 ? 'Paket dibuat beserta tagihannya.' : 'Paket gratis langsung aktif.');
    }

    public function cancel(StudentPackage $studentPackage): RedirectResponse
    {
        if ($studentPackage->usages()->exists()) {
            return back()->with('error', 'Paket yang sudah terpakai tidak bisa dibatalkan.');
        }

        $studentPackage->update(['status' => 'cancelled']);

        return back()->with('success', 'Paket dibatalkan.');
    }

    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sessions_count' => ['required', 'integer', 'min:1', 'max:200'],
            'price' => ['required', 'integer', 'min:0'],
            'valid_days' => ['required', 'integer', 'min:1', 'max:730'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ], [], ['sessions_count' => 'jumlah sesi', 'price' => 'harga', 'valid_days' => 'masa berlaku']);
    }
}
