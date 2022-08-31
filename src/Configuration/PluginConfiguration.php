<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class PluginConfiguration
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getApiHost(): string
    {
        return $this->systemConfigService->getString(PluginConfigurationValueNames::API_HOST);
    }

    public function getApiKey(): string
    {
        return $this->systemConfigService->getString(PluginConfigurationValueNames::API_KEY);
    }

    public function getClientSecret(): ?string
    {
        return $this->systemConfigService->getString(PluginConfigurationValueNames::CLIENT_SECRET);
    }
}
