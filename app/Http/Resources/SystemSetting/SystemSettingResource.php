<?php

namespace App\Http\Resources\SystemSetting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tab_switch' => $this->value['tab_switch'] ?? null,
            'fullscreen' => $this->value['fullscreen'] ?? null,
        ];
    }
}
