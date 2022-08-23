<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage\CheckoutConfirmPageLoadedEventHandler;
use Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage\CheckoutConfirmPageLoadedEventSubscriber;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;

class CheckoutConfirmPageLoadedEventSubscriberTest extends TestCase
{
    /** @var PluginConfigurationValidator&MockObject */
    private PluginConfigurationValidator $pluginConfigurationValidator;

    /** @var CheckoutConfirmPageLoadedEventHandler&MockObject */
    private CheckoutConfirmPageLoadedEventHandler $checkoutConfirmPageLoadedEventHandler;

    /** @var ErrorHandler&MockObject */
    private ErrorHandler $errorHandler;
    
    private CheckoutConfirmPageLoadedEventSubscriber $sut;

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

    public function test_onCheckoutConfirmPageLoaded_is_subscribed_to_CheckoutConfirmPageLoadedEvent(): void
    {
        $subscribedEvents = $this->sut->getSubscribedEvents();

        $this->assertEquals(
            $subscribedEvents[CheckoutConfirmPageLoadedEvent::class], 
            'onCheckoutConfirmPageLoaded');
    }

    public function test_onCheckoutConfirmPageLoaded_plugin_configuraton_is_valid_executes_handler(): void
    {
        $event = $this->createMock(CheckoutConfirmPageLoadedEvent::class);

        $this->pluginConfigurationValidator->method('isInvalid')->willReturn(false);

        $this->checkoutConfirmPageLoadedEventHandler
            ->expects($this->once())
            ->method('handle')
            ->with($event);

        $this->sut->onCheckoutConfirmPageLoaded($event);
    }

    public function test_onCheckoutConfirmPageLoaded_plugin_configuraton_is_invalid_does_not_execute_handler(): void
    {
        $event = $this->createMock(CheckoutConfirmPageLoadedEvent::class);

        $this->pluginConfigurationValidator->method('isInvalid')->willReturn(true);

        $this->checkoutConfirmPageLoadedEventHandler
            ->expects($this->never())
            ->method('handle')
            ->with($event);

        $this->sut->onCheckoutConfirmPageLoaded($event);
    }

    public function test_onCheckoutConfirmPageLoaded_reprots_errors(): void
    {
        $event = $this->createMock(CheckoutConfirmPageLoadedEvent::class);
        $exception = new Exception();

        $this->pluginConfigurationValidator->method('isInvalid')->willReturn(false);

        $this->checkoutConfirmPageLoadedEventHandler
            ->method('handle')
            ->willThrowException($exception);

        $this->errorHandler
            ->expects($this->once())
            ->method('handle')
            ->with($exception);

        $this->sut->onCheckoutConfirmPageLoaded($event);
    }
}