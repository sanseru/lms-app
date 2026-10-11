<?php

namespace Modules\Kanban\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $card = $this->route('card');

        return $card
            ? $this->user()->can('editCard', [$card->board, $card])
            : $this->user()->can('work', $this->route('board'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'judul kartu'];
    }
}
