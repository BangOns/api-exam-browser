<?php

namespace App\Http\Requests\Question;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "question" => "required | string",
            "lesson_id" => "required | exists:lessons,id",
            "type" => "required | in:Multiple Choice,Essay",
            "options" => "nullable | required_if:type,Multiple Choice|array",
            "correct_answer" => "nullable | required_if:type,Multiple Choice|string",
            "rubric" => "nullable | required_if:type,Essay|string",
            "max_points" => "required | integer",
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
            "options.array" => ":attribute is required",
            "correct_answer.string" => ":attribute is required",
            "rubric.string" => ":attribute is required",
            "max_points.integer" => ":attribute is required",
        ];
    }
}
