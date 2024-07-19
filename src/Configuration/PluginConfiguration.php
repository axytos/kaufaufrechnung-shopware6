<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

use Axytos\KaufAufRechnung\Shopware\CronJob\OrderSyncCronJobIntervalInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PluginConfiguration
{
    /**
     * @var \Shopware\Core\System\SystemConfig\SystemConfigService
     */
    private $systemConfigService;

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

    public function getAfterCheckoutOrderStatus(): AfterCheckoutOrderStatus
    {
        $value = $this->systemConfigService->getString(PluginConfigurationValueNames::AFTER_CHECKOUT_ORDER_STATUS);

        return new AfterCheckoutOrderStatus($value);
    }

    public function getAfterCheckoutPaymentStatus(): AfterCheckoutPaymentStatus
    {
        $value = $this->systemConfigService->getString(PluginConfigurationValueNames::AFTER_CHECKOUT_PAYMENT_STATUS);

        return new AfterCheckoutPaymentStatus($value);
    }

    public function getOrderSyncCronJobInterval(): OrderSyncCronJobIntervalInterface
    {
        $key = $this->systemConfigService->getString(PluginConfigurationValueNames::ORDER_SYNC_CRONJOB_INTERVAL);

        return OrderSyncCronJobInterval::create($key);
    }
}
