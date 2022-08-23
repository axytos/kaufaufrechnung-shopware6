<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Shopware\Core\ShippingOrderEventsSubscriber;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Framework\Context;

class ShippingOrderEventsSubscriberTest extends TestCase 
{
    const ORDER_ID = 'orderId';

    /** @var ErrorHandler&MockObject */
    private ErrorHandler $errorHandler;

    /** @var InvoiceClientInterface&MockObject */
    private InvoiceClientInterface $invoiceClient;

    /** @var InvoiceOrderContextFactory&MockObject */
    private InvoiceOrderContextFactory $invoiceOrderContextFactory;

    /** @var OrderCheckProcessStateMachine&MockObject */
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;

    /** @var PluginConfigurationValidator&MockObject */
    private PluginConfigurationValidator $pluginConfigurationValidator;

    /** @var ShippingOrderEventsSubscriber */
    private ShippingOrderEventsSubscriber $sut;

    /** @var OrderStateMachineStateChangeEvent&MockObject */
    private OrderStateMachineStateChangeEvent $event;

    /** @var InvoiceOrderContext&MockObject */
    private InvoiceOrderContext $invoiceOrderContext;

    /** @var Context&MockObject */
    private Context $context;

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
        $subscribedEvents = $this->sut->getSubscribedEvents();

        $this->assertContains('onShipped', $subscribedEvents);
    }

    /**
     * @dataProvider dataProvider_test_onShipped_reports_shipping
     */
    public function test_onShipped_reports_shipping(bool $configIsInvalid, string $orderState, InvocationOrder $expectedInvocationOrder): void
    {
        $this->setUpPluginConfigurationValidation($configIsInvalid);
        $this->setUpOrderState($orderState);

        $this->invoiceClient
            ->expects($expectedInvocationOrder)
            ->method('reportShipping')
            ->with($this->invoiceOrderContext);

        $this->sut->onShipped($this->event);
    }

    public function dataProvider_test_onShipped_reports_shipping(): array
    {
        return [
            [true, OrderCheckProcessStates::UNCHECKED, $this->never()],
            [true, OrderCheckProcessStates::CHECKED, $this->never()],
            [true, OrderCheckProcessStates::FAILED, $this->never()],
            [true, OrderCheckProcessStates::CONFIRMED, $this->never()],
            
            [false, OrderCheckProcessStates::UNCHECKED, $this->never()],
            [false, OrderCheckProcessStates::CHECKED, $this->never()],
            [false, OrderCheckProcessStates::FAILED, $this->never()],
            [false, OrderCheckProcessStates::CONFIRMED, $this->once()]
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