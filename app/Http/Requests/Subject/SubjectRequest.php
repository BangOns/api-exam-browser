<?php

namespace App\Http\Requests\Subject;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class SubjectRequest extends FormRequest
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
            'name' => 'rquired|string|max:255',
        ];
    }
    public function attributes(): array
    {
        return [
            'name' => 'Nama Mata Pelajaran',
        ];
    }
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa string.',
            'max' => ':attribute maksimal :max karakter.',
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
