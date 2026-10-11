<?php

namespace Modules\Sessions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Sessions\Models\SessionAttendance;

class SessionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('session');

        // Admins pass Gate::before, so cancelled/rescheduled status is checked explicitly.
        return $session->isReportable() && $this->user()->can('report', $session);
    }

    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:255'],
            'homework' => ['nullable', 'string', 'max:2000'],
            'parent_note' => ['nullable', 'string', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'flagged' => ['boolean'],
            'recording_url' => ['nullable', 'url', 'max:255'],
            'attendances' => ['present', 'array'],
            'attendances.*.student_id' => ['required', 'integer'],
            'attendances.*.status' => ['required', Rule::in(array_keys(SessionAttendance::STATUSES))],
            'attendances.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'topic' => 'topic covered',
            'homework' => 'homework',
            'parent_note' => 'note for parents',
            'recording_url' => 'recording link',
        ];
    }
}
