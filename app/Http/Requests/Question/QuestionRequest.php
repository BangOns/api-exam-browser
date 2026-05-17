<?php

namespace App\Http\Requests\Question;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isMultipleChoice = $this->input('type') === 'Multiple Choice';

        return [
            'question'   => ['required', 'string', 'min:1', 'max:5000'],
            'lesson_id'  => ['required', 'uuid', 'exists:lessons,id'],
            'type'       => ['required', Rule::in(['Multiple Choice', 'Essay'])],

            // ✅ FIX: Tambah min:2 agar minimal ada 2 pilihan
            'options'    => [
                'nullable',
                'required_if:type,Multiple Choice',
                'array',
                'min:2',
                'max:20',
            ],

            // ✅ FIX: Label dibatasi max:5 (misal: A, B, C, D, atau A1)
            'options.*.label' => ['required_with:options', 'string', 'max:5'],
            'options.*.text'  => ['required_with:options', 'string', 'max:500'],

            // ✅ FIX: Validasi correct_answer harus salah satu dari label yang dikirim
            'correct_answer' => [
                'nullable',
                'required_if:type,Multiple Choice',
                'string',
                'max:500',
                $isMultipleChoice
                    ? Rule::in(collect($this->input('options', []))->pluck('label')->toArray())
                    : 'sometimes',
            ],

            'rubric'     => ['nullable', 'required_if:type,Essay', 'string', 'min:1', 'max:5000'],
            'max_points' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'question'        => 'Question',
            'lesson_id'       => 'Lesson',
            'type'            => 'Question type',
            'options'         => 'Options',
            'options.*.label' => 'Option label',
            'options.*.text'  => 'Option text',
            'correct_answer'  => 'Correct answer',
            'rubric'          => 'Rubric',
            'max_points'      => 'Max points',
        ];
    }

    public function messages(): array
    {
        return [
            // Required
            'required'                  => ':attribute is required.',
            'required_if'               => ':attribute is required.',
            'required_with'             => ':attribute is required.',

            // Type
            'string'                    => ':attribute must be a string.',
            'integer'                   => ':attribute must be a number.',
            'array'                     => ':attribute must be a list.',
            'boolean'                   => ':attribute must be true or false.',

            // Format
            'uuid'                      => ':attribute must be a valid UUID.',
            'type.in'                   => ':attribute must be either Multiple Choice or Essay.',

            // Length
            'min'                       => ':attribute must be at least :min characters.',
            'max'                       => ':attribute must not exceed :max characters.',
            'options.min'               => 'Options must have at least :min choices.',
            'options.max'               => 'Options must not exceed :max choices.',

            // Exists
            'lesson_id.exists'          => 'Selected lesson does not exist.',

            // ✅ FIX: Pesan khusus correct_answer harus dari pilihan yang tersedia
            'correct_answer.in'         => 'Correct answer must match one of the provided option labels.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException(
            $validator,
            response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
