<?php

namespace App\Http\Requests\Question;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class QuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "question" => "required|string|min:1|max:5000",
            "lesson_id" => "required|uuid|exists:lessons,id",
            "type" => "required|in:Multiple Choice,Essay",
            "options" => "nullable|required_if:type,Multiple Choice|array|max:20",
            "options.*" => "string|max:500",
            "correct_answer" => "nullable|required_if:type,Multiple Choice|string|max:500",
            "rubric" => "nullable|required_if:type,Essay|string|min:1|max:5000",
            "max_points" => "required|integer|min:1|max:1000",
        ];
    }
    public function attributes(): array
    {
        return [
            "question" => "Question",
            "lesson_id" => "Lesson",
            "type" => "Type",
            "options" => "Options",
            "correct_answer" => "Correct answer",
            "rubric" => "Rubric",
            "max_points" => "Max points",
        ];
    }
    public function messages(): array
    {
        return [
            "required" => ":attribute is required",
            "required_if" => ":attribute is required",
            "lesson_id.exists" => ":attribute is required",
            "type.in" => ":attribute is required",
            "array" => ":attribute is required",
            "string" => ":attribute is required",
            "integer" => ":attribute is required",

        ];
    }
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status' => false,
            'message' => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
