<?php

namespace App\Http\Requests\Exam;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
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
            "name" => "required|min:1",
            "subject_id" => "required | exists:subjects,id",
            "class_id" => "required | exists:classes,id",
            "status" => "required|in:draft,active,scheduled,completed",
            "questions" => "nullable|array",
            "questions.*" => "nullable|exists:questions,id",
        ];
    }
    public function attributes()
    {
        return [
            "name" => "Nama",
            "subject_id" => "Subject",
            "class_id" => "Class",
            "status" => "Status",
            "questions" => "Soal",
            "questions.*" => "ID Soal",
        ];
    }
    public function messages()
    {
        return [
            "required" => ":attribute is required",
            "min" => ":attribute is too short",
            "exists" => ":attribute is not found",
            "in" => ":attribute is invalid",
            "array" => ":attribute must be an array",
        ];
    }
}
