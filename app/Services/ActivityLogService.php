<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityLogService
{
    /**
     * Create a new class instance.
     */
    private const MAX_PER_PAGE = 5;
    public function getAllActivityLogs(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return ActivityLog::with('user')->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->paginate($perPage);
    }
    public function log(User $user, string $action, string $module)
    {

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'module' => $module,
        ]);
    }
}
