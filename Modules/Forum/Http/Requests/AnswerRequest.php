<?php

namespace Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $answer = $this->route('answer');

        return $answer
            ? $this->user()->can('update', $answer)
            : $this->user()->can('answer', $this->route('question'));
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'code' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return ['body' => 'jawaban'];
    }
}
