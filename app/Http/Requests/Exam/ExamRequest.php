<?php
namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => "required|string|min:3|max:255",
            "lesson_id" => "required|uuid|exists:lessons,id",
            "status" => "required|in:draft,active,scheduled,completed",
            "questions" => "nullable|array|max:500",
            "questions.*" => "uuid|exists:questions,id",
            // ✅ tambah integer, min, max
            "pg_weight" => "required|integer|min:0|max:100",
            "essay_weight" => "required|integer|min:0|max:100",
        ];
    }

    // ✅ Tambah validasi total bobot harus 100
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $essay = $this->input("essay_weight", 0);
            $pg = $this->input("pg_weight", 0);

            if ((int) $essay + (int) $pg !== 100) {
                $validator
                    ->errors()
                    ->add(
                        "pg_weight",
                        "Total bobot essay dan pilihan ganda harus 100%",
                    );
            }
        });
    }

    public function attributes(): array
    {
        return [
            "name" => "Nama",
            "lesson_id" => "Mata Pelajaran",
            "status" => "Status",
            "questions" => "Soal",
            "questions.*" => "ID Soal",
            "pg_weight" => "Bobot Pilihan Ganda",
            "essay_weight" => "Bobot Essay",
        ];
    }

    public function messages(): array
    {
        return [
            "required" => ":attribute wajib diisi",
            "min" => ":attribute terlalu pendek",
            "max" => ":attribute melebihi batas maksimal",
            "exists" => ":attribute tidak ditemukan",
            "in" => ":attribute tidak valid",
            "array" => ":attribute harus berupa array",
            "integer" => ":attribute harus berupa bilangan bulat",
        ];
    }
}
