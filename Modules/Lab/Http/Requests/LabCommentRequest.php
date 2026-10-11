<?php

namespace Modules\Lab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LabCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('interact', $this->route('project'));
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:1000']];
    }

    public function attributes(): array
    {
        return ['body' => 'comment'];
    }
}
