<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung\Shopware\Core\CancelOrderEventSubscriber;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Framework\Context;

class CancelOrderEventSubscriberTest extends TestCase
{
    const ORDER_ID = 'orderId';

    /** @var InvoiceClientInterface&MockObject*/
    private InvoiceClientInterface $invoiceClient;

    /** @var ErrorHandler&MockObject*/
    private ErrorHandler $errorHandler;

    /** @var PluginConfigurationValidator&MockObject*/
    private PluginConfigurationValidator $pluginConfigurationValidator;

    /** @var OrderCheckProcessStateMachine&MockObject*/
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;

    /** @var InvoiceOrderContextFactory&MockObject*/
    private InvoiceOrderContextFactory $invoiceOrderContextFactory;

    private CancelOrderEventSubscriber $sut;

    /** @var OrderStateMachineStateChangeEvent&MockObject*/
    private OrderStateMachineStateChangeEvent $event;

    /** @var Context&MockObject*/
    private Context $context;

    /** @var InvoiceOrderContext&MockObject*/
    private InvoiceOrderContext $invoiceOrderContext;

    public function setUp(): void
    {
        $this->invoiceClient = $this->createMock(InvoiceClientInterface::class);
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->pluginConfigurationValidator = $this->createMock(PluginConfigurationValidator::class);
        $this->orderCheckProcessStateMachine = $this->createMock(OrderCheckProcessStateMachine::class);
        $this->invoiceOrderContextFactory = $this->createMock(InvoiceOrderContextFactory::class);

        $this->sut = new CancelOrderEventSubscriber(
            $this->invoiceClient,
            $this->errorHandler,
            $this->pluginConfigurationValidator,
            $this->orderCheckProcessStateMachine,
            $this->invoiceOrderContextFactory
        );

        $this->event = $this->createMock(OrderStateMachineStateChangeEvent::class);
        $this->context = $this->createMock(Context::class);
        $this->invoiceOrderContext = $this->createMock(InvoiceOrderContext::class);
        
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

    private function setUpPluginConfigurationIsInvalid(bool $isValid): void
    {
        $this->pluginConfigurationValidator
            ->method('isInvalid')
            ->willReturn($isValid);
    }

    public function setUpOrderState(string $orderState): void
    {
        $this->orderCheckProcessStateMachine
            ->method('getState')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($orderState);
    }

    /**
     * @dataProvider dataProvider_test_onOrderStateCancelled_calls_invoiceClient
     */
    public function test_onOrderStateCancelled_calls_invoiceClient(bool $configIsInvalid, string $orderState, InvocationOrder $expectedInvocationOrder): void
    {
        $this->setUpPluginConfigurationIsInvalid($configIsInvalid);
        $this->setUpOrderState($orderState);

        $this->invoiceClient
            ->expects($expectedInvocationOrder)
            ->method('cancelOrder')
            ->with($this->invoiceOrderContext);

        $this->sut->onOrderStateCancelled($this->event);
    }

    public function dataProvider_test_onOrderStateCancelled_calls_invoiceClient(): array
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
}
