<?php

namespace App\Http\Requests\ExamSchedule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExamScheduleRequest extends FormRequest
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
            'exam_id'    => 'required|exists:exams,id',
            'exam_date'  => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'duration'   => 'required|integer|min:1',
            'status'     => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'exam_id'    => 'Ujian',
            'exam_date'  => 'Tanggal Ujian',
            'start_time' => 'Waktu Mulai',
            'end_time'   => 'Waktu Selesai',
            'duration'   => 'Durasi',
            'status'     => 'Status',
        ];
    }

    public function messages(): array
    {
        return [
            'required'    => ':attribute wajib diisi',
            'exists'      => ':attribute tidak ditemukan',
            'date'        => ':attribute harus berupa tanggal yang valid',
            'date_format' => ':attribute harus dalam format HH:MM',
            'after'       => ':attribute harus lebih dari :date',
            'string'      => ':attribute harus berupa teks',
            'integer'     => ':attribute harus berupa angka bulat',
            'min'         => ':attribute minimal :min menit',
        ];
    }
}
