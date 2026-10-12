<?php

namespace Modules\Lab\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
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
        $project = $this->route('project');
        $creating = ! $project;

        return [
            'title' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'language' => [$creating ? 'required' : 'prohibited', Rule::in(array_keys(LabProject::LANGUAGES))],
            'code' => [$creating || $project->usesFile() ? 'nullable' : 'present', 'nullable', 'string', 'max:100000'],
            // Scratch: the editor sends the whole project as an .sb3 file on every save.
            'file' => [$project?->usesFile() ? 'nullable' : 'prohibited', 'file', 'max:'.LabProject::MAX_FILE_KB],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $file = $this->file('file');

            // An .sb3 is a zip archive; anything else is not a Scratch project.
            if ($file && ! $validator->errors()->has('file')) {
                $handle = fopen($file->getRealPath(), 'rb');
                $magic = $handle ? fread($handle, 4) : '';
                $handle && fclose($handle);

                if ($magic !== "PK\x03\x04") {
                    $validator->errors()->add('file', 'That is not a Scratch project (.sb3).');
                }
            }
        }];
    }

    public function attributes(): array
    {
        return ['language' => 'project type', 'file' => 'Scratch project'];
    }
}
