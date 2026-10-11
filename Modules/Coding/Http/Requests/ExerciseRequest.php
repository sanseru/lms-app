<?php

namespace Modules\Coding\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Coding\Grading\CodeRules;
use Modules\Coding\Models\CodeExercise;

class ExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('learning.manage');
    }

    public function rules(): array
    {
        $mode = $this->input('mode');
        $language = $this->input('language');
        $testKinds = match ($mode) {
            'html' => array_keys(CodeExercise::HTML_RULES),
            'function' => ['function'],
            default => ['io'],
        };

        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:20000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'is_published' => ['boolean'],
            'language' => ['required', Rule::in(array_keys(CodeExercise::LANGUAGES))],
            'mode' => ['required', Rule::in(CodeExercise::MODES_BY_LANGUAGE[$language] ?? [])],
            'starter_code' => ['nullable', 'string', 'max:20000'],
            'solution_code' => ['required', 'string', 'max:20000'],
            'function_name' => [Rule::requiredIf($mode === 'function'), 'nullable', 'regex:/^[A-Za-z_][A-Za-z0-9_]*$/', 'max:60'],
            'hints' => ['nullable', 'array', 'max:5'],
            'hints.*' => ['nullable', 'string', 'max:500'],
            'time_limit_ms' => ['required', 'integer', 'min:500', 'max:10000'],
            'show_solution_after' => ['nullable', 'integer', 'min:1', 'max:20'],
            'case_sensitive' => ['boolean'],
            'concept' => ['nullable', 'string', 'max:40'],
            'level' => ['nullable', 'integer', 'min:1', 'max:5'],

            'rules' => ['nullable', 'array', 'max:10'],
            'rules.*.type' => ['required', Rule::in(array_keys(CodeRules::TYPES))],
            'rules.*.value' => ['required', 'string', 'max:60'],
            'rules.*.message' => ['nullable', 'string', 'max:255'],

            'tests' => [$mode === 'parsons' ? 'nullable' : 'required', 'array', 'max:30'],
            'tests.*.id' => ['nullable', 'integer'],
            'tests.*.kind' => ['required', Rule::in($testKinds)],
            'tests.*.input' => [$mode === 'html' ? 'required' : 'nullable', 'string', 'max:5000'],
            'tests.*.expected' => [in_array($mode, ['io', 'function'], true) ? 'required' : 'nullable', 'string', 'max:5000'],
            'tests.*.property' => ['nullable', 'string', 'max:60'],
            'tests.*.is_hidden' => ['boolean'],
            'tests.*.weight' => ['required', 'integer', 'min:1', 'max:10'],
            'tests.*.fail_message' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->input('mode') === 'parsons' && count(array_filter(preg_split('/\R/', (string) $this->input('solution_code')), fn ($l) => trim($l) !== '')) < 2) {
                $validator->errors()->add('solution_code', 'Soal susun baris butuh minimal 2 baris kode.');
            }

            foreach ($this->input('rules', []) ?? [] as $i => $rule) {
                if (($rule['type'] ?? null) === 'max_lines' && (! ctype_digit((string) ($rule['value'] ?? '')) || (int) $rule['value'] < 1)) {
                    $validator->errors()->add("rules.$i.value", 'Enter a whole number of lines, e.g. 6.');
                }
            }

            foreach ($this->input('tests', []) ?? [] as $i => $test) {
                $kind = $test['kind'] ?? null;

                if ($kind === 'function') {
                    if (! is_array(json_decode((string) ($test['input'] ?? ''), true)) || ! array_is_list(json_decode((string) $test['input'], true))) {
                        $validator->errors()->add("tests.$i.input", 'Argumen harus berupa daftar JSON, mis. [3, 4] atau ["halo"].');
                    }
                    json_decode((string) ($test['expected'] ?? ''));
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $validator->errors()->add("tests.$i.expected", 'Hasil harus berupa nilai JSON, mis. 12, "teks", atau [1, 2].');
                    }
                }

                if (in_array($kind, ['attr', 'style'], true) && blank($test['property'] ?? null)) {
                    $validator->errors()->add("tests.$i.property", 'Isi nama atribut atau properti CSS.');
                }

                if (in_array($kind, ['text', 'style', 'count'], true) && blank($test['expected'] ?? null)) {
                    $validator->errors()->add("tests.$i.expected", 'Isi nilai yang diharapkan.');
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul', 'body' => 'cerita soal', 'language' => 'bahasa', 'mode' => 'jenis penilaian',
            'solution_code' => 'solusi', 'function_name' => 'nama fungsi', 'time_limit_ms' => 'batas waktu',
            'tests' => 'tes', 'rules.*.value' => 'nilai aturan', 'tests.*.input' => 'input', 'tests.*.expected' => 'hasil yang diharapkan', 'tests.*.weight' => 'bobot',
        ];
    }
}
