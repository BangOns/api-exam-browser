<?php

namespace App\Http\Requests\ExamAttempt;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequest extends FormRequest
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
            "answers" => "nullable|array",
            "answers.*.question_id" => "required|string",
            "answers.*.answer" => "required|string",
        ];
    }
    public function attributes(): array
    {
        return [
            "answers" => "Jawaban",
            "answers.*.question_id" => "ID Soal",
            "answers.*.answer" => "Jawaban",
        ];
    }
    public function messages(): array
    {
        return [
            "required" => ":attribute wajib diisi",
            "array" => ":attribute harus berupa array",
            "string" => ":attribute harus berupa string",
        ];
    }
}
