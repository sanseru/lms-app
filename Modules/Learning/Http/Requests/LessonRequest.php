<?php

namespace Modules\Learning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\QuizQuestion;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('learning.manage');
    }

    public function rules(): array
    {
        $quiz = $this->input('type') === 'quiz';

        return [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(Lesson::TYPES))],
            'body' => ['nullable', 'string', 'max:50000'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'is_published' => ['boolean'],
            'assessment' => ['nullable', Rule::in(array_keys(Lesson::ASSESSMENTS))],
            'pass_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],

            'questions' => [$quiz ? 'required' : 'nullable', 'array', 'max:50'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.prompt' => ['required', 'string', 'max:2000'],
            'questions.*.type' => ['required', Rule::in(array_keys(QuizQuestion::TYPES))],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:20'],
            'questions.*.explanation' => ['nullable', 'string', 'max:2000'],
            'questions.*.concept' => ['nullable', 'string', 'max:40'],
            'questions.*.options' => ['required', 'array', 'min:1', 'max:6'],
            'questions.*.options.*.text' => ['required', 'string', 'max:500'],
            'questions.*.options.*.correct' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->input('questions', []) ?? [] as $i => $q) {
                $type = $q['type'] ?? 'single';
                $options = $q['options'] ?? [];
                $correct = collect($options)->filter(fn ($o) => ! empty($o['correct']))->count();

                if ($type === 'short') {
                    continue; // setiap opsi adalah jawaban yang diterima; minimal satu sudah dijaga rules()
                }

                if ($type === 'true_false' && count($options) !== 2) {
                    $validator->errors()->add("questions.$i.options", 'A true/false question needs exactly two options.');
                } elseif (count($options) < 2) {
                    $validator->errors()->add("questions.$i.options", 'Add at least two options.');
                } elseif ($correct === 0) {
                    $validator->errors()->add("questions.$i.options", 'Tandai minimal satu jawaban benar.');
                } elseif (in_array($type, ['single', 'true_false'], true) && $correct > 1) {
                    $validator->errors()->add("questions.$i.options", 'Soal satu jawaban hanya boleh punya satu jawaban benar.');
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul', 'type' => 'jenis', 'body' => 'isi', 'video_url' => 'link video',
            'duration_minutes' => 'durasi', 'pass_score' => 'nilai lulus', 'max_attempts' => 'batas percobaan', 'time_limit_minutes' => 'batas waktu',
            'max_score' => 'nilai maksimal', 'questions' => 'soal', 'questions.*.prompt' => 'pertanyaan',
            'questions.*.options' => 'pilihan jawaban', 'questions.*.options.*.text' => 'teks pilihan', 'questions.*.points' => 'poin',
        ];
    }
}
