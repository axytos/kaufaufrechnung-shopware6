<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Configuration;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Configuration\ClientSecretProviderInterface;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;

class ClientSecretProvider implements ClientSecretProviderInterface
{
    /**
     * @var PluginConfiguration
     */
    private $pluginConfiguration;

    public function __construct(PluginConfiguration $pluginConfiguration)
    {
        $this->pluginConfiguration = $pluginConfiguration;
    }

    /**
     * @return string|null
     */
    public function getClientSecret()
    {
        return $this->pluginConfiguration->getClientSecret();
    }
}
