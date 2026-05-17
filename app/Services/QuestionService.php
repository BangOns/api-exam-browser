<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Question;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionService
{
    // Batas maksimum item per halaman
    private const MAX_PER_PAGE = 100;
    /**
     * Create a new class instance.
     */

    public function getAllQuestions(
        int $perPage = 5,
        string $search = "",
    ): LengthAwarePaginator {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $query = Question::select(
            "id",
            "question",
            "lesson_id",
            "type",
            "options",
            "correct_answer",
            "rubric",
            "max_points",
            "created_at",
            "updated_at",
        )->with("lesson");

        if (!empty($search)) {
            $query = SearchService::apply($query, $search, "search_vector");
        }

        return $query->paginate($perPage);
    }
    public function getQuestionById($id)
    {
        $question = Question::where("id", $id)
            ->select(
                "id",
                "question",
                "lesson_id",
                "type",
                "options",
                "correct_answer",
                "rubric",
                "max_points",
                "created_at",
                "updated_at",
            )
            ->with("lesson")
            ->first();
        if (!$question) {
            throw new DataNotFound("Pertanyaan tidak ditemukan");
        }
        return $question;
    }
    public function createQuestion(array $data)
    {
        $data = $this->sanitizeQuestionData($data);
        Log::info("sebelum save", ["question" => $data["question"]]);
        $question = DB::transaction(function () use ($data) {
            return Question::create($data);
        });

        Log::info("sesudah save", ["question" => $question->question]);
        return $question;
    }
    public function updateQuestion(array $data, $id)
    {
        Log::info("sebelum save", ["question" => $data["question"]]);

        $question = Question::where("id", $id)->first();

        if (!$question) {
            throw new DataNotFound("Pertanyaan tidak ditemukan");
        }

        $data = $this->sanitizeQuestionData($data);
        $resultQuestion = DB::transaction(function () use ($data, $question) {
            $question->update($data);
            return $question->fresh();
        });
        Log::info("sesudah save", ["question" => $resultQuestion->question]);
        return $resultQuestion;
    }

    /**
     * Sanitize and validate question data
     */
    // private function sanitizeQuestionData(array $data): array
    // {
    //     // Type validation
    //     if ($data['type'] === 'Multiple Choice') {
    //         if (empty($data['options']) || empty($data['correct_answer'])) {
    //             throw new \Exception('Multiple Choice must have options and correct answer');
    //         }
    //     }

    //     if ($data['type'] === 'Essay' && empty($data['rubric'])) {
    //         throw new \Exception('Essay must have rubric');
    //     }

    //     // Sanitize text inputs
    //     $data['question'] = strip_tags(
    //         html_entity_decode(
    //             $data['question'],
    //             ENT_QUOTES | ENT_HTML5,
    //             'UTF-8'
    //         ),
    //         '<p><br><strong><em>'
    //     );
    //     if (isset($data['rubric'])) {
    //         $data['rubric'] = strip_tags(
    //             html_entity_decode(
    //                 $data['rubric'],
    //                 ENT_QUOTES | ENT_HTML5,
    //                 'UTF-8'
    //             ),
    //             '<p><br><strong><em>'
    //         );
    //     }

    //     // Encode JSON with security flags to prevent injection
    //     if (isset($data['options'])) {
    //         $data['options'] = json_encode($data['options'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    //     }

    //     // Sanitize correct answer
    //     if (isset($data['correct_answer'])) {
    //         $data['correct_answer'] = strip_tags($data['correct_answer']);
    //     }

    //     return $data;
    // }
    private function sanitizeQuestionData(array $data): array
    {
        Log::info("belum sanitzire", $data);

        // Validasi tipe (defense in depth, seharusnya sudah lewat FormRequest)
        if ($data["type"] === "Multiple Choice") {
            if (empty($data["options"]) || empty($data["correct_answer"])) {
                throw new \Exception(
                    "Multiple Choice must have options and correct answer",
                );
            }
        }

        if ($data["type"] === "Essay" && empty($data["rubric"])) {
            throw new \Exception("Essay must have rubric");
        }

        // ✅ FIX: Hapus html_entity_decode — simpan data raw ke DB
        // Cukup strip_tags untuk buang tag HTML yang tidak diizinkan
        $allowedTags = "<p><br><strong><em>";

        $data["question"] = strip_tags($data["question"], $allowedTags);

        // ✅ FIX: Gunakan $data['rubric'], bukan $data['question']
        if (isset($data["rubric"])) {
            $data["rubric"] = strip_tags($data["rubric"], $allowedTags);
        }

        // ✅ FIX: Hapus JSON_HEX_APOS, JSON_HEX_QUOT, JSON_HEX_AMP
        // Flag tersebut untuk output HTML, bukan untuk disimpan ke DB
        if (isset($data["options"])) {
            $data["options"] = json_encode($data["options"]);
        }

        if (isset($data["correct_answer"])) {
            $data["correct_answer"] = strip_tags($data["correct_answer"]);
        }
        Log::info("sudah sanitzire", $data);

        return $data;
    }
    public function deleteQuestion($id)
    {
        $question = Question::where("id", $id)->first();
        if (!$question) {
            throw new DataNotFound("Pertanyaan tidak ditemukan");
        }
        $resultQuestion = DB::transaction(function () use ($question) {
            $question->delete();
            return $question;
        });
        return $resultQuestion;
    }
}
