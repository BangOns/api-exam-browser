<?php

namespace App\Http\Requests\ActivityLog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ActivityLogRequest extends FormRequest
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
            "user_id" => "required|exists:users,id",
            "action" => "required|in:create,update,delete,submit,violation",
            "module" => "required|in:question,exam,setting,attempt",
        ];
    }
    public function attributes()
    {
        return [
            "user_id" => "User",
            "action" => "Aksi",
            "module" => "Modul",
        ];
    }
    public function messages()
    {
        return [
            "required" => ":attribute wajib diisi",
            "exists" => ":attribute tidak ditemukan",
            "in" => ":attribute tidak valid",
        ];
    }
}
