<?php

namespace Modules\Lab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Lab\Models\LabProject;

class LabProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project ? $project->isOwnedBy($this->user()) : $this->user()->can('lab.use');
    }

    public function rules(): array
    {
        $creating = ! $this->route('project');

        return [
            'title' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'language' => [$creating ? 'required' : 'prohibited', Rule::in(array_keys(LabProject::LANGUAGES))],
            'code' => [$creating ? 'nullable' : 'present', 'nullable', 'string', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return ['language' => 'project type'];
    }
}
