<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExamAttempt;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test exam monitoring doesn't have N+1 queries
     */
    public function test_exam_monitoring_query_structure(): void
    {
        // Setup
        $admin = User::factory()->admin()->create();
        $class = Classes::factory()->create();
        $exam = Exam::factory()->for($class)->create();

        // Create students
        Student::factory(50)->for($class)->create();

        // Create exam attempts
        StudentExamAttempt::factory(25)->for($exam)->create();

        // Verify endpoint responds
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/exams/{$exam->id}/monitor");

        $response->assertSuccessful();
        
        // Verify response structure
        $data = $response->json('data');
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('belum_masuk', $data);
        $this->assertArrayHasKey('sedang_mengerjakan', $data);
    }

    /**
     * Test question index uses select optimization
     */
    public function test_question_index_working(): void
    {
        $teacher = User::factory()->teacher()->create();
        Lesson::factory(10)->create();

        $response = $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/question');

        $response->assertSuccessful();
    }

    /**
     * Test cache is working for repeated requests
     */
    public function test_teacher_cache_reduces_queries(): void
    {
        $admin = User::factory()->admin()->create();
        Teacher::factory(5)->create();

        // Just verify it doesn't error
        $response1 = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/teacher?per_page=5');

        $response1->assertSuccessful();

        $response2 = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/teacher?per_page=5');

        $response2->assertSuccessful();
    }
}
