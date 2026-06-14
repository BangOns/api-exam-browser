<?php
namespace App\Http\Requests\ExamAttempt;

use Illuminate\Foundation\Http\FormRequest;

class GradeEssayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // "attempt_id" => "required|uuid|exists:student_exam_attempts,id",
            "answers" => "required|array",
            "answers.*.question_id" => "required|uuid|exists:questions,id",
            "answers.*.score" => "required|integer|min:0|max:100",
        ];
    }

    public function attributes(): array
    {
        return [
            "attempt_id" => "ID Attempt",
            "answers" => "Jawaban",
            "answers.*.question_id" => "ID Soal",
            "answers.*.score" => "Nilai",
        ];
    }

    public function messages(): array
    {
        return [
            "required" => ":attribute wajib diisi",
            "uuid" => ":attribute harus berupa UUID",
            "exists" => ":attribute tidak ditemukan",
            "integer" => ":attribute harus berupa bilangan bulat",
            "min" => ":attribute minimal 0",
            "max" => ":attribute maksimal 100",
        ];
    }
}
