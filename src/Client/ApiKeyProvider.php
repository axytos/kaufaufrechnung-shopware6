<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Client;

use Axytos\ECommerce\Abstractions\ApiKeyProviderInterface;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;

class ApiKeyProvider implements ApiKeyProviderInterface
{
    public PluginConfiguration $pluginConfig;

    public function __construct(PluginConfiguration $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;
    }

    public function getApiKey(): string
    {
        return $this->pluginConfig->getApiKey();
    }
}