<?php

namespace Modules\Classes\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Classes\Models\ClassRoom;
use Modules\Identity\Enums\Role;

class ClassRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('classes.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:60'],
            'level' => ['required', Rule::in(array_keys(ClassRoom::LEVELS))],
            'description' => ['nullable', 'string', 'max:2000'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'zoom_url' => ['nullable', 'url', 'max:255'],
            'zoom_meeting_id' => ['nullable', 'string', 'max:40'],
            'zoom_passcode' => ['nullable', 'string', 'max:40'],
            'schedule_days' => ['required', 'array', 'min:1'],
            'schedule_days.*' => ['integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(array_keys(ClassRoom::STATUSES))],
            'color' => ['required', Rule::in(ClassRoom::COLORS)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama kelas',
            'subject' => 'mata pelajaran',
            'teacher_id' => 'guru',
            'schedule_days' => 'hari',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
            'zoom_url' => 'link Zoom',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $teacherId = $this->integer('teacher_id') ?: null;

            if ($teacherId && ! User::find($teacherId)?->hasRole(Role::Teacher->value)) {
                $validator->errors()->add('teacher_id', 'User yang dipilih bukan guru.');

                return;
            }

            if (in_array($this->input('status'), ['active', 'upcoming'])) {
                $conflict = ClassRoom::conflictsFor(
                    $teacherId,
                    array_map('intval', $this->input('schedule_days')),
                    $this->input('start_time'),
                    $this->input('end_time'),
                    $this->route('class')?->id,
                );

                if ($conflict) {
                    $validator->errors()->add('teacher_id', "Jadwal guru bentrok dengan kelas {$conflict->name} ({$conflict->scheduleLabel()}).");
                }
            }
        });
    }
}
