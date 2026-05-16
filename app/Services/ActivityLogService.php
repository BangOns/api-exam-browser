<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ActivityLogService
{
    /**
     * Create a new class instance.
     */
    public function getAllActivityLogs(?string $search = null): Collection
    {
        return ActivityLog::with('user')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->get();
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
