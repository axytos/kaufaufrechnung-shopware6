<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\AccountEditOrderPage;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountEditOrderPageLoadedEventSubscriber implements EventSubscriberInterface
{
    private PluginConfigurationValidator $pluginConfigurationValidator;
    private AccountEditOrderPageLoadedEventHandler $accountEditOrderPageLoadedEventHandler;
    private ErrorHandler $errorHandler;

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
            AccountEditOrderPageLoadedEvent::class => 'onAccountEditOrderPageLoaded'
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
