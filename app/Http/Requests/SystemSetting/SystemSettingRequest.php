<?php

namespace App\Http\Requests\SystemSetting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class SystemSettingRequest extends FormRequest
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
            'tab_switch.enabled' => 'required|boolean',
            'fullscreen.enabled' => 'required|boolean',

        ];
    }
    public function attributes()
    {
        return [
            'tab_switch.enabled' => 'Tab Switch Enabled',
            'fullscreen.enabled' => 'Fullscreen Enabled',
        ];
    }
    public function messages()
    {
        return [
            'required' => ':attribute is required',
            'boolean' => ':attribute must be a boolean',
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
