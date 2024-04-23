<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Shopware\Core\ShippingOrderEventsSubscriber;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Framework\Context;

class ShippingOrderEventsSubscriberTest extends TestCase
{
    const ORDER_ID = 'orderId';

    /** @var ErrorHandler&MockObject */
    private $errorHandler;

    /** @var InvoiceClientInterface&MockObject */
    private $invoiceClient;

    /** @var InvoiceOrderContextFactory&MockObject */
    private $invoiceOrderContextFactory;

    /** @var OrderCheckProcessStateMachine&MockObject */
    private $orderCheckProcessStateMachine;

    /** @var PluginConfigurationValidator&MockObject */
    private $pluginConfigurationValidator;

    /** @var ShippingOrderEventsSubscriber */
    private $sut;

    /** @var OrderStateMachineStateChangeEvent&MockObject */
    private $event;

    /** @var InvoiceOrderContext&MockObject */
    private $invoiceOrderContext;

    /** @var Context&MockObject */
    private $context;

    public function setUp(): void
    {
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->invoiceClient = $this->createMock(InvoiceClientInterface::class);
        $this->invoiceOrderContextFactory = $this->createMock(InvoiceOrderContextFactory::class);
        $this->orderCheckProcessStateMachine = $this->createMock(OrderCheckProcessStateMachine::class);
        $this->pluginConfigurationValidator = $this->createMock(PluginConfigurationValidator::class);
        $this->sut = new ShippingOrderEventsSubscriber(
            $this->errorHandler,
            $this->invoiceClient,
            $this->invoiceOrderContextFactory,
            $this->orderCheckProcessStateMachine,
            $this->pluginConfigurationValidator
        );

        $this->event = $this->createMock(OrderStateMachineStateChangeEvent::class);
        $this->invoiceOrderContext = $this->createMock(InvoiceOrderContext::class);
        $this->context = $this->createMock(Context::class);
        $this->setUpInvoiceOrderContext();
    }

    private function setUpInvoiceOrderContext(): void
    {
        $this->event
            ->method('getOrderId')
            ->willReturn(self::ORDER_ID);

        $this->event
            ->method('getContext')
            ->willReturn($this->context);

        $this->invoiceOrderContextFactory
            ->method('getInvoiceOrderContext')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($this->invoiceOrderContext);
    }

    private function setUpPluginConfigurationValidation(bool $isInvalid): void
    {
        $this->pluginConfigurationValidator
            ->method('isInvalid')
            ->willReturn($isInvalid);
    }

    private function setUpOrderState(string $orderState): void
    {
        $this->orderCheckProcessStateMachine
            ->method('getState')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($orderState);
    }

    public function test_getSubscribedEvents_registers_onShipped(): void
    {
        $subscribedEvents = ShippingOrderEventsSubscriber::getSubscribedEvents();

        $this->assertContains('onShipped', $subscribedEvents);
    }

    /**
     * @dataProvider dataProvider_test_onShipped_reports_shipping
     */
    #[DataProvider('dataProvider_test_onShipped_reports_shipping')]
    public function test_onShipped_reports_shipping(bool $configIsInvalid, string $orderState, int $expectedInvocationCount): void
    {
        $this->setUpPluginConfigurationValidation($configIsInvalid);
        $this->setUpOrderState($orderState);

        $this->invoiceClient
            ->expects($this->exactly($expectedInvocationCount))
            ->method('reportShipping')
            ->with($this->invoiceOrderContext);

        $this->sut->onShipped($this->event);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProvider_test_onShipped_reports_shipping(): array
    {
        return [
            [true, OrderCheckProcessStates::UNCHECKED, 0],
            [true, OrderCheckProcessStates::CHECKED, 0],
            [true, OrderCheckProcessStates::FAILED, 0],
            [true, OrderCheckProcessStates::CONFIRMED, 0],

            [false, OrderCheckProcessStates::UNCHECKED, 0],
            [false, OrderCheckProcessStates::CHECKED, 0],
            [false, OrderCheckProcessStates::FAILED, 0],
            [false, OrderCheckProcessStates::CONFIRMED, 1]
        ];
    }

    public function test_onShipped_handles_errors(): void
    {
        $this->setUpPluginConfigurationValidation(false);
        $this->setUpOrderState(OrderCheckProcessStates::CONFIRMED);

        $exception = new Exception();

        $this->invoiceClient
            ->method('reportShipping')
            ->willThrowException($exception);

        $this->errorHandler
            ->expects($this->once())
            ->method('handle')
            ->with($exception);

        $this->sut->onShipped($this->event);
    }
}
