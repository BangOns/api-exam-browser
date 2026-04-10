<?php

namespace App\Http\Resources\ActivityLog;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "user" => [
                "id" => $this->user->id,
                "name" => $this->user->name,
                "role" => $this->user->role,
            ],
            "action" => $this->action,
            "module" => $this->module,

        ];
    }
}
