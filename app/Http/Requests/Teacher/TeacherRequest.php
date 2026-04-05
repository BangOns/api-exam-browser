<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class TeacherRequest extends FormRequest
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
            'username'  => 'required|string|max:255|unique:users,username',
            'password'  => 'required|string|min:8',
            "nip"       => "required|unique:teachers,nip",
            "lessons"   => "nullable|array",
            "lessons.*.class_id" => "nullable|exists:classes,id",
            "lessons.*.subject_id" => "required_with:lessons.*.class_id|exists:subjects,id",
        ];
    }

    public function attributes(): array
    {
        return [
            "full_name" => "Nama Lengkap",
            "username" => "Username",
            "password" => "Password",
            "nip" => "NIP",
            "lessons" => "Pelajaran",
            "lessons.*.class_id" => "Kelas",
            "lessons.*.subject_id" => "Mata Pelajaran",
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
