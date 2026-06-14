<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    /**
     * Test XSS protection in responses
     */
    public function test_xss_protection_in_question_response(): void
    {
        $admin = User::factory()->admin()->create();

        $maliciousPayload = [
            "question" => '<img src=x onerror="alert(1)">',
            "lesson_id" => "550e8400-e29b-41d4-a716-446655440001",
            "type" => "Multiple Choice",
            "options" => ["<script>alert(1)</script>", "option2"],
            "correct_answer" => "<script>alert(1)</script>",
        ];

        $response = $this->actingAs($admin, "sanctum")->postJson(
            "/api/question",
            $maliciousPayload,
        );

        $response->assertStatus(422); // Should fail validation or sanitize

        // If it passes, verify HTML is escaped
        if ($response->status() !== 422) {
            $data = $response->json("data");
            $this->assertStringNotContainsString(
                "<img",
                $data["question"] ?? "",
            );
            $this->assertStringNotContainsString(
                "<script>",
                $data["question"] ?? "",
            );
        }
    }

    /**
     * Test authorization enforcement
     */
    public function test_unauthorized_request_denied(): void
    {
        $response = $this->postJson("/api/question", [
            "question" => "Test",
            "lesson_id" => "test-id",
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test rate limiting on login attempts
     */
    public function test_rate_limiting_after_failed_attempts(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson("/api/login", [
                "username" => "testuser",
                "password" => "wrongpassword",
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson("/api/login", [
            "username" => "testuser",
            "password" => "wrongpassword",
        ]);

        $this->assertTrue(
            $response->status() === 429 ||
                str_contains($response->json("message"), "terlalu banyak"),
        );
    }

    /**
     * Test CORS headers are properly set
     */
    public function test_security_headers_present(): void
    {
        $response = $this->getJson("/api/question");

        $this->assertTrue($response->headers->has("X-Frame-Options"));
        $this->assertTrue($response->headers->has("X-Content-Type-Options"));
        $this->assertTrue($response->headers->has("X-XSS-Protection"));
        $this->assertTrue($response->headers->has("Content-Security-Policy"));
    }

    /**
     * Test exception messages don't leak database details
     */
    public function test_exception_messages_sanitized(): void
    {
        // Try to trigger a query exception
        $response = $this->actingAs(
            User::factory()->admin()->create(),
            "sanctum",
        )->postJson("/api/question", [
            "question" => "Test",
            "lesson_id" => "invalid-uuid-format",
            "type" => "Multiple Choice",
        ]);

        $message = $response->json("message");

        // Should not contain SQL details
        $this->assertStringNotContainsString("foreign key", $message);
        $this->assertStringNotContainsString("SQL", $message);
        $this->assertStringNotContainsString("syntax", $message);
    }
}
