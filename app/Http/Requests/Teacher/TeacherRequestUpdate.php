<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class TeacherRequestUpdate extends FormRequest
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
            'full_name' => 'required|string|max:255',
            'username'  => 'required|string|max:255',
            'password'  => 'required|string|min:8',
            "nip"       => "required",
            'class_id'  => 'nullable|array',
            'class_id.*' => 'exists:classes,id',
        ];
    }

    public function attributes(): array
    {
        return [
            "full_name" => "Nama Lengkap",
            "username" => "Username",
            "password" => "Password",
            "nip" => "NIP",
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
