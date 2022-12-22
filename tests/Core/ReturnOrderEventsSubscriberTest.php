<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\Core\ReturnOrderEventsSubscriber;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Framework\Context;

class ReturnOrderEventsSubscriberTest extends TestCase
{
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

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Core\ReturnOrderEventsSubscriber
     */
    private $sut;

    public function setUp(): void
    {
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->invoiceClient = $this->createMock(InvoiceClientInterface::class);
        $this->invoiceOrderContextFactory = $this->createMock(InvoiceOrderContextFactory::class);
        $this->orderCheckProcessStateMachine = $this->createMock(OrderCheckProcessStateMachine::class);
        $this->pluginConfigurationValidator = $this->createMock(PluginConfigurationValidator::class);

        $this->sut = new ReturnOrderEventsSubscriber($this->errorHandler, $this->invoiceClient, $this->invoiceOrderContextFactory, $this->orderCheckProcessStateMachine, $this->pluginConfigurationValidator);
    }

    public function test_getSubscribedEvents_subscribes_onReturned_method(): void
    {
        $subscription = $this->sut->getSubscribedEvents();

        $subscribedMethod = array_values($subscription)[0];

        $this->assertEquals('onReturned', $subscribedMethod);
    }

    /**
     * @dataProvider dataProvider_test_onReturned_invokes_invoice_client
     */
    public function test_onReturned_invokes_invoice_client(bool $pluginConfigIsInvalid, string $orderCheckState, InvokedCount $expectedInvocationCount): void
    {
        $orderId = 'orderId';
        $context = $this->createMock(Context::class);
        $invoiceOrderContext = $this->createMock(InvoiceOrderContext::class);

        /** @var OrderStateMachineStateChangeEvent&MockObject */
        $event = $this->createMock(OrderStateMachineStateChangeEvent::class);
        $event->method('getOrderId')->willReturn($orderId);
        $event->method('getContext')->willReturn($context);

        $this->invoiceOrderContextFactory
            ->method('getInvoiceOrderContext')
            ->with($orderId, $context)
            ->willReturn($invoiceOrderContext);

        $this->pluginConfigurationValidator
            ->method('isInvalid')
            ->willReturn($pluginConfigIsInvalid);

        $this->orderCheckProcessStateMachine
            ->method('getState')
            ->with($orderId, $context)
            ->willReturn($orderCheckState);

        $this->invoiceClient
            ->expects($expectedInvocationCount)
            ->method('returnOrder')
            ->with($invoiceOrderContext);

        $this->sut->onReturned($event);
    }

    public function dataProvider_test_onReturned_invokes_invoice_client(): array
    {
        return [
            [true, OrderCheckProcessStates::UNCHECKED, $this->never()],
            [true, OrderCheckProcessStates::CHECKED, $this->never()],
            [true, OrderCheckProcessStates::CONFIRMED, $this->never()],
            [true, OrderCheckProcessStates::FAILED, $this->never()],

            [false, OrderCheckProcessStates::UNCHECKED, $this->never()],
            [false, OrderCheckProcessStates::CHECKED, $this->never()],
            [false, OrderCheckProcessStates::CONFIRMED, $this->once()],
            [false, OrderCheckProcessStates::FAILED, $this->never()],
        ];
    }

    public function test_onReturned_sends_errors(): void
    {
        $this->pluginConfigurationValidator->method('isInvalid')->willReturn(false);
        $this->orderCheckProcessStateMachine->method('getState')->willReturn(OrderCheckProcessStates::CONFIRMED);

        /** @var OrderStateMachineStateChangeEvent&MockObject */
        $event = $this->createMock(OrderStateMachineStateChangeEvent::class);
        $event->method('getOrderId')->willReturn('orderId');

        $exception = new \Exception();

        $this->invoiceClient
            ->method('returnOrder')
            ->willThrowException($exception);

        $this->errorHandler
            ->expects($this->once())
            ->method('handle')
            ->with($exception);

        $this->sut->onReturned($event);
    }
}
