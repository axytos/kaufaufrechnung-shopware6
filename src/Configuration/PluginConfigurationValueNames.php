<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

abstract class PluginConfigurationValueNames
{
    public const API_KEY = 'AxytosKaufAufRechnung.config.apiKey';
    public const API_HOST = 'AxytosKaufAufRechnung.config.apiHost';
    public const CLIENT_SECRET = 'AxytosKaufAufRechnung.config.clientSecret';
    public const AFTER_CHECKOUT_ORDER_STATUS = 'AxytosKaufAufRechnung.config.afterCheckoutOrderStatus';
    public const AFTER_CHECKOUT_PAYMENT_STATUS = 'AxytosKaufAufRechnung.config.afterCheckoutPaymentStatus';
    public const ERROR_MESSAGE = 'AxytosKaufAufRechnung.config.errorMessage';
    public const ORDER_SYNC_CRONJOB_INTERVAL = 'AxytosKaufAufRechnung.config.orderSyncCronJobInterval';
    public const PRECHECK_CONTROL = 'AxytosKaufAufRechnung.config.precheckControl';
}
