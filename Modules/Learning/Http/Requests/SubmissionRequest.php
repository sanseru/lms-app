<?php

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');

        return $lesson->type === 'assignment' && $this->user()->can('learn', $lesson);
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:10000'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'file' => ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,pdf,zip,sb3,py,html,txt,js,css'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $existing = $this->route('lesson')->submissions()->where('student_id', $this->user()->id)->first();

            if ($existing?->status === 'graded') {
                $validator->errors()->add('body', 'This assignment has been graded and can no longer be resubmitted.');
            }

            if (! $this->filled('body') && ! $this->filled('link_url') && ! $this->hasFile('file') && ! $existing?->file_path) {
                $validator->errors()->add('body', 'Write an answer, add a link, or upload a file.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['body' => 'answer', 'link_url' => 'link', 'file' => 'file'];
    }
}
