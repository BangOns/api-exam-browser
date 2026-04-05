<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentRequestUpdate extends FormRequest
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
            "full_name" => "required|string|max:255",
            'username' => [
                'required',
                'string',
                'max:255',

            ],
            "password" => "required|string|min:8",
            "nisn" => [
                "required",
                "string",
                "max:10",

            ],
            "class_id" => "nullable|exists:classes,id",
        ];
    }

    public function attributes(): array
    {
        return [
            "full_name" => "Nama Lengkap",
            "username" => "Username",
            "password" => "Password",
            "nisn" => "NISN",
            "class_id" => "Kelas",
        ];
    }
    public function messages(): array
    {
        return [
            "required" => ":attribute is required",
            "exists" => ":attribute does not exist",
            "unique" => ":attribute already exists",
            "in" => ":attribute must be one of: admin, student, teacher",
            "min" => ":attribute must be at least 8 characters",
            "max" => ":attribute must be at most 255 characters",
            "array" => ":attribute must be an array",
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
