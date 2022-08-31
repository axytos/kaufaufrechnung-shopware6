<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Client;

use Axytos\ECommerce\Abstractions\FallbackModeConfigurationInterface;
use Axytos\ECommerce\Abstractions\FallbackModes;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;

class FallbackModeConfiguration implements FallbackModeConfigurationInterface
{
    public PluginConfiguration $pluginConfig;

    public function __construct(PluginConfiguration $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;
    }

    public function getFallbackMode(): string
    {
        return FallbackModes::ALL_PAYMENT_METHODS;
    }
}
