<?php

namespace Modules\Certificates\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Classes\Models\ClassRoom;

class CertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('certificates.issue');
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'achievement' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return ['student_id' => 'siswa', 'class_room_id' => 'kelas', 'title' => 'judul', 'achievement' => 'keterangan', 'issued_at' => 'tanggal terbit'];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $class = ClassRoom::find($this->input('class_room_id'));

            if (! $this->user()->hasRole('admin') && $class->teacher_id !== $this->user()->id) {
                $validator->errors()->add('class_room_id', 'Kamu hanya bisa menerbitkan sertifikat untuk kelas yang kamu ajar.');
            }
            if (! $class->students()->whereKey($this->input('student_id'))->exists()) {
                $validator->errors()->add('student_id', 'Siswa ini tidak terdaftar di kelas tersebut.');
            }
            if ($this->filled('course_id') && (int) $this->input('course_id') !== $class->course_id) {
                $validator->errors()->add('course_id', 'Kursus tidak sesuai dengan kelas.');
            }
            if (! $this->filled('course_id') && ! $this->filled('title')) {
                $validator->errors()->add('title', 'Isi judul sertifikat.');
            }
        }];
    }
}
