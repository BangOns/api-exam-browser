<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
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
            'role'      => 'nullable|in:admin,student,teacher',
        ];
    }
    public function attributes()
    {
        return [
            'full_name' => 'full name',
            'username' => 'username',
            'password' => 'password',
            'role' => 'role'
        ];
    }
    public function messages()
    {
        return [
            'required' => ':attribute is required',
            'unique' => ':attribute already exists',
            'min' => ':attribute must be at least 8 characters',
            'in' => ':attribute must be one of the following: admin, student, teacher',
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
