<?php

namespace Modules\Leads\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Classes\Models\ClassRoom;
use Modules\Identity\Enums\Role;
use Modules\Leads\Models\Lead;
use Modules\People\Actions\SavePerson;

/**
 * Calon siswa jadi siswa: buat akun anak, pakai akun orang tua yang sudah
 * ada (nomor HP sama, mis. kakaknya sudah les) atau buat baru, lalu
 * daftarkan ke kelas bila dipilih.
 */
class ConvertLead
{
    public function __construct(private SavePerson $people) {}

    /**
     * @param  array{student_username: string, guardian_username?: string|null, password: string, class_room_id?: int|null, relationship?: string}  $data
     */
    public function handle(Lead $lead, array $data, User $by): User
    {
        return DB::transaction(function () use ($lead, $data, $by) {
            $guardian = $this->existingGuardian($lead) ?? $this->people->guardian([
                'name' => $lead->parent_name,
                'username' => $data['guardian_username'],
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => 'active',
                'password' => $data['password'],
            ]);

            $student = $this->people->student([
                'name' => $lead->child_name,
                'username' => $data['student_username'],
                'status' => 'active',
                'password' => $data['password'],
                'grade' => $lead->child_grade,
                'joined_at' => today(),
                'notes' => $lead->interest ? "Minat awal: {$lead->interest}" : null,
            ]);
            $guardian->children()->syncWithoutDetaching([$student->id => ['relationship' => $data['relationship'] ?? 'wali']]);

            if ($classId = $data['class_room_id'] ?? null) {
                ClassRoom::findOrFail($classId)->students()->syncWithoutDetaching([$student->id => ['status' => 'active', 'joined_at' => now()]]);
            }

            $lead->update(['status' => 'won', 'student_id' => $student->id, 'closed_at' => now()]);
            $lead->notes()->create(['user_id' => $by->id, 'type' => 'status', 'body' => "Jadi siswa. Akun {$student->username} dibuat, orang tua {$guardian->name}."]);

            return $student;
        });
    }

    public function existingGuardian(Lead $lead): ?User
    {
        $phone = preg_replace('/\D/', '', $lead->phone);

        return User::role(Role::Guardian->value)
            ->where(fn ($q) => $q->whereRaw("replace(replace(replace(phone, '-', ''), ' ', ''), '+', '') in (?, ?)", [$phone, $lead->waNumber()])
                ->when($lead->email, fn ($q, $email) => $q->orWhere('email', $email)))
            ->first();
    }
}
