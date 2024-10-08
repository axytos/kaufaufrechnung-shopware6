<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage\CheckoutConfirmPageLoadedEventHandler;
use Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage\CheckoutConfirmPageLoadedEventSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;

/**
 * @internal
 */
class CheckoutConfirmPageLoadedEventSubscriberTest extends TestCase
{
    /** @var PluginConfigurationValidator&MockObject */
    private $pluginConfigurationValidator;

    /** @var CheckoutConfirmPageLoadedEventHandler&MockObject */
    private $checkoutConfirmPageLoadedEventHandler;

    /** @var ErrorHandler&MockObject */
    private $errorHandler;

    /**
     * @var CheckoutConfirmPageLoadedEventSubscriber
     */
    private $sut;

    public function setUp(): void
    {
        $this->pluginConfigurationValidator = $this->createMock(PluginConfigurationValidator::class);
        $this->checkoutConfirmPageLoadedEventHandler = $this->createMock(CheckoutConfirmPageLoadedEventHandler::class);
        $this->errorHandler = $this->createMock(ErrorHandler::class);

        $this->sut = new CheckoutConfirmPageLoadedEventSubscriber(
            $this->pluginConfigurationValidator,
            $this->checkoutConfirmPageLoadedEventHandler,
            $this->errorHandler
        );
    }

    public function test_on_checkout_confirm_page_loaded_is_subscribed_to_checkout_confirm_page_loaded_event(): void
    {
        $subscribedEvents = CheckoutConfirmPageLoadedEventSubscriber::getSubscribedEvents();

        $this->assertEquals(
            $subscribedEvents[CheckoutConfirmPageLoadedEvent::class],
            'onCheckoutConfirmPageLoaded'
        );
    }

    public function test_on_checkout_confirm_page_loaded_plugin_configuraton_is_valid_executes_handler(): void
    {
        $event = $this->createMock(CheckoutConfirmPageLoadedEvent::class);

        $this->pluginConfigurationValidator->method('isInvalid')->willReturn(false);

        $this->checkoutConfirmPageLoadedEventHandler
            ->expects($this->once())
            ->method('handle')
            ->with($event)
        ;

        $this->sut->onCheckoutConfirmPageLoaded($event);
    }

    public function test_on_checkout_confirm_page_loaded_plugin_configuraton_is_invalid_does_not_execute_handler(): void
    {
        $event = $this->createMock(CheckoutConfirmPageLoadedEvent::class);

        $this->pluginConfigurationValidator->method('isInvalid')->willReturn(true);

        $this->checkoutConfirmPageLoadedEventHandler
            ->expects($this->never())
            ->method('handle')
            ->with($event)
        ;

        $this->sut->onCheckoutConfirmPageLoaded($event);
    }

    public function test_on_checkout_confirm_page_loaded_reprots_errors(): void
    {
        $event = $this->createMock(CheckoutConfirmPageLoadedEvent::class);
        $exception = new \Exception();

        $this->pluginConfigurationValidator->method('isInvalid')->willReturn(false);

        $this->checkoutConfirmPageLoadedEventHandler
            ->method('handle')
            ->willThrowException($exception)
        ;

        $this->errorHandler
            ->expects($this->once())
            ->method('handle')
            ->with($exception)
        ;

        $this->sut->onCheckoutConfirmPageLoaded($event);
    }
}
