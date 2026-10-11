<?php

namespace Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Classes\Models\ClassRoom;
use Modules\Forum\Models\Question;
use Modules\Learning\Models\Lesson;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        if ($question) {
            return $this->user()->can('update', $question);
        }

        $class = ClassRoom::find($this->input('class_room_id'));

        return ! $class || $this->user()->can('askIn', [Question::class, $class]);
    }

    public function rules(): array
    {
        return [
            'class_room_id' => [$this->route('question') ? 'prohibited' : 'required', 'integer', 'exists:class_rooms,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'code' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['boolean'],
            'visibility' => ['required', Rule::in(array_keys(Question::VISIBILITIES))],
            'anonymous' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['class_room_id' => 'kelas', 'lesson_id' => 'pelajaran', 'title' => 'judul', 'body' => 'pertanyaan', 'image' => 'gambar'];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $this->filled('lesson_id') || $validator->errors()->isNotEmpty()) {
                return;
            }

            $classId = $this->route('question')?->class_room_id ?? $this->input('class_room_id');
            $lesson = Lesson::find($this->input('lesson_id'));

            if ($lesson->course_id === null || $lesson->course_id !== ClassRoom::find($classId)?->course_id) {
                $validator->errors()->add('lesson_id', 'Pelajaran ini bukan bagian dari materi kelas tersebut.');
            }
        }];
    }
}
