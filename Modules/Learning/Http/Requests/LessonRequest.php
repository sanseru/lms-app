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
            'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],

            'questions' => [$quiz ? 'required' : 'nullable', 'array', 'max:50'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.prompt' => ['required', 'string', 'max:2000'],
            'questions.*.type' => ['required', Rule::in(array_keys(QuizQuestion::TYPES))],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:20'],
            'questions.*.explanation' => ['nullable', 'string', 'max:2000'],
            'questions.*.concept' => ['nullable', 'string', 'max:40'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*.text' => ['required', 'string', 'max:500'],
            'questions.*.options.*.correct' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->input('questions', []) ?? [] as $i => $q) {
                $correct = collect($q['options'] ?? [])->filter(fn ($o) => ! empty($o['correct']))->count();

                if ($correct === 0) {
                    $validator->errors()->add("questions.$i.options", 'Mark at least one correct answer.');
                } elseif (($q['type'] ?? 'single') === 'single' && $correct > 1) {
                    $validator->errors()->add("questions.$i.options", 'A one-answer question can only have one correct answer.');
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'title' => 'title', 'type' => 'type', 'body' => 'content', 'video_url' => 'video link',
            'duration_minutes' => 'duration', 'pass_score' => 'pass score', 'max_attempts' => 'attempt limit',
            'max_score' => 'maximum score', 'questions' => 'questions', 'questions.*.prompt' => 'question',
            'questions.*.options' => 'answer options', 'questions.*.options.*.text' => 'option text', 'questions.*.points' => 'points',
        ];
    }
}
