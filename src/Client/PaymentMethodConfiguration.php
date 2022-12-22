<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Client;

use Axytos\ECommerce\Abstractions\PaymentMethodConfigurationInterface;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;

class PaymentMethodConfiguration implements PaymentMethodConfigurationInterface
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration
     */
    public $pluginConfig;

    public function __construct(PluginConfiguration $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;
    }

    /**
     * @param string $paymentMethodId
     * @return bool
     */
    public function isIgnored($paymentMethodId)
    {
        return false;
    }

    /**
     * @param string $paymentMethodId
     * @return bool
     */
    public function isSafe($paymentMethodId)
    {
        return false;
    }

    /**
     * @param string $paymentMethodId
     * @return bool
     */
    public function isUnsafe($paymentMethodId)
    {
        return false;
    }

    /**
     * @param string $paymentMethodId
     * @return bool
     */
    public function isNotConfigured($paymentMethodId)
    {
        return true;
    }
}
