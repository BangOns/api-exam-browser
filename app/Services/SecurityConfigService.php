<?php

namespace App\Services;

class SecurityConfigService
{
    protected SystemSettingService $settingService;

    public function __construct(SystemSettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function build(): array
    {
        $global = $this->settingService->get('exam_security', []);

        return [
            'tab_switch' => [
                'enabled' => $global['tab_switch']['enabled'] ?? false,
            ],
            'fullscreen' => [
                'enabled' => $global['fullscreen']['enabled'] ?? false,
            ],
        ];
    }
}
