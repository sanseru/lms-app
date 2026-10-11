<?php

namespace Modules\Sessions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reschedule', $this->route('session'));
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['date' => 'tanggal', 'start_time' => 'jam mulai', 'end_time' => 'jam selesai', 'reason' => 'alasan'];
    }
}
