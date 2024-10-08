<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\AccountEditOrderPage;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountEditOrderPageLoadedEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var AccountEditOrderPageLoadedEventHandler
     */
    private $accountEditOrderPageLoadedEventHandler;
    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    public function __construct(
        PluginConfigurationValidator $pluginConfigurationValidator,
        AccountEditOrderPageLoadedEventHandler $accountEditOrderPageLoadedEventHandler,
        ErrorHandler $errorHandler
    ) {
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->accountEditOrderPageLoadedEventHandler = $accountEditOrderPageLoadedEventHandler;
        $this->errorHandler = $errorHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountEditOrderPageLoadedEvent::class => 'onAccountEditOrderPageLoaded',
        ];
    }

    public function onAccountEditOrderPageLoaded(AccountEditOrderPageLoadedEvent $event): void
    {
        try {
            if ($this->pluginConfigurationValidator->isInvalid()) {
                return;
            }
            $this->accountEditOrderPageLoadedEventHandler->handle($event);
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);
        }
    }
}
