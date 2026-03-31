<?php

namespace App\Http\Requests\Class;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ClassRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:255',
            'department' => 'required|string|max:255',
        ];
    }
    public function attributes()
    {
        return [
            'name' => 'name',
            'level' => 'level',
            'department' => 'department',
        ];
    }
    public function messages()
    {
        return [
            'required' => ':attribute is required',
            'unique' => ':attribute already exists',
            'min' => ':attribute must be at least 8 characters',
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
