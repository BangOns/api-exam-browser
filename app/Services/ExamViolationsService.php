<?php

namespace App\Services;

use App\Models\ExamViolation;

class ExamViolationsService
{
    /**
     * Create a new class instance.
     */
    public function handleViolation($attempt, string $type)
    {
        $config = $attempt->security_config;

        if (!isset($config[$type]) || !($config[$type]['enabled'] ?? false)) {
            return $attempt;
        }

        // 🔥 simpan log dulu
        ExamViolation::create([
            'attempt_id' => $attempt->id,
            'type' => $type,
        ]);
    }
}
