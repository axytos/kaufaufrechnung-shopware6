<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutConfirmPageLoadedEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var CheckoutConfirmPageLoadedEventHandler
     */
    private $checkoutConfirmPageLoadedEventHandler;
    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    public function __construct(
        PluginConfigurationValidator $pluginConfigurationValidator,
        CheckoutConfirmPageLoadedEventHandler $checkoutConfirmPageLoadedEventHandler,
        ErrorHandler $errorHandler
    ) {
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->checkoutConfirmPageLoadedEventHandler = $checkoutConfirmPageLoadedEventHandler;
        $this->errorHandler = $errorHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
        ];
    }

    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        try {
            if ($this->pluginConfigurationValidator->isInvalid()) {
                return;
            }
            $this->checkoutConfirmPageLoadedEventHandler->handle($event);
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);
        }
    }
}
