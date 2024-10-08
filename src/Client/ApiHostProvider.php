<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Client;

use Axytos\ECommerce\Abstractions\ApiHostProviderInterface;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;

class ApiHostProvider implements ApiHostProviderInterface
{
    /**
     * @var PluginConfiguration
     */
    public $pluginConfig;

    public function __construct(PluginConfiguration $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;
    }

    /**
     * @phpstan-return self::LIVE|self::SANDBOX
     */
    public function getApiHost(): string
    {
        $option = $this->pluginConfig->getApiHost();
        switch ($option) {
            case 'APIHOST_LIVE':
                return self::LIVE;
            case 'APIHOST_SANDBOX':
                return self::SANDBOX;
            default:
                return self::SANDBOX;
        }
    }
}
