<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests;

use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\Shopware\PaymentMethod\PaymentMethodCollectionFilter;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Shopware\Storefront\AccountEditOrderPage\AccountEditOrderPageLoadedEventHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;

class AccountEditOrderPageLoadedEventHandlerTest extends TestCase
{
    /** @var OrderCheckProcessStateMachine&MockObject $orderCheckProcessStateMachine */
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;

    /** @var PaymentMethodCollectionFilter&MockObject $paymentMethodCollectionFilter */
    private PaymentMethodCollectionFilter $paymentMethodCollectionFilter;

    private AccountEditOrderPageLoadedEventHandler $sut;

    private const ORDER_ID = 'orderId';

    /** @var OrderEntity&MockObject $order */
    private OrderEntity $order;

    /** @var SalesChannelContext&MockObject $salesChannelContext */
    private SalesChannelContext $salesChannelContext;

    /** @var Context&MockObject $context */
    private Context $context;

    /** @var AccountEditOrderPage&MockObject $page */
    private AccountEditOrderPage $page;

    /** @var AccountEditOrderPageLoadedEvent&MockObject $event */
    private AccountEditOrderPageLoadedEvent $event;

    private PaymentMethodCollection $paymentMethods;
    private PaymentMethodCollection $allowedFallbackPaymentMethods;
    private PaymentMethodCollection $notUnsafePaymentMethods;
    private PaymentMethodCollection $noAxytosInvoicePaymentMethods;

    public function setUp(): void
    {
        $this->orderCheckProcessStateMachine = $this->createMock(OrderCheckProcessStateMachine::class);
        $this->paymentMethodCollectionFilter = $this->createMock(PaymentMethodCollectionFilter::class);

        $this->sut = new AccountEditOrderPageLoadedEventHandler(
            $this->orderCheckProcessStateMachine,
            $this->paymentMethodCollectionFilter
        );

        $this->order = $this->createMock(OrderEntity::class);
        $this->salesChannelContext = $this->createMock(SalesChannelContext::class);
        $this->context = $this->createMock(Context::class);
        $this->page = $this->createMock(AccountEditOrderPage::class);
        $this->event = $this->createMock(AccountEditOrderPageLoadedEvent::class);

        $this->paymentMethods = $this->createMock(PaymentMethodCollection::class);
        $this->allowedFallbackPaymentMethods = $this->createMock(PaymentMethodCollection::class);
        $this->notUnsafePaymentMethods = $this->createMock(PaymentMethodCollection::class);
        $this->noAxytosInvoicePaymentMethods = $this->createMock(PaymentMethodCollection::class);

        $this->setUpEvent();
        $this->setUpPaymentMethods();
    }

    private function setUpEvent(): void
    {
        $this->order->method('getId')->willReturn(self::ORDER_ID);
        $this->page->method('getOrder')->willReturn($this->order);   
        $this->event->method('getPage')->willReturn($this->page);
        $this->event->method('getSalesChannelContext')->willReturn($this->salesChannelContext);
        $this->event->method('getContext')->willReturn($this->context);
    }

    private function setUpPaymentMethods(): void
    {
        $this->page->method('getPaymentMethods')->willReturn($this->paymentMethods);
        
        $this->paymentMethodCollectionFilter
            ->method('filterAllowedFallbackPaymentMethods')
            ->with($this->paymentMethods)
            ->willReturn($this->allowedFallbackPaymentMethods);

        $this->paymentMethodCollectionFilter
            ->method('filterPaymentMethodsNotUsingHandler')
            ->with($this->paymentMethods, AxytosInvoicePaymentHandler::class)
            ->willReturn($this->noAxytosInvoicePaymentMethods);

        $this->paymentMethodCollectionFilter
            ->method('filterNotUnsafePaymentMethods')
            ->with($this->noAxytosInvoicePaymentMethods)
            ->willReturn($this->notUnsafePaymentMethods);
    }

    private function setUpPaymentControlOrderState(string $paymentControlOrderState): void
    {
        $this->orderCheckProcessStateMachine
            ->method('getState')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($paymentControlOrderState);
    }

    public function test_handle_CHECKED_order_does_not_alter_payment_methods(): void
    {
        $this->setUpPaymentControlOrderState(OrderCheckProcessStates::CHECKED);

        $this->page->expects($this->once())
            ->method('setPaymentMethods')
            ->with($this->notUnsafePaymentMethods);

        $this->sut->handle($this->event);
    }

    public function test_handle_CONFIRMED_order_does_not_alter_payment_methods(): void
    {
        $this->setUpPaymentControlOrderState(OrderCheckProcessStates::CONFIRMED);

        $this->page->expects($this->never())
            ->method('setPaymentMethods');

        $this->sut->handle($this->event);
    }

    public function test_handle_FAILED_order_sets_allowed_fallback_payment_methods(): void
    {
        $this->setUpPaymentControlOrderState(OrderCheckProcessStates::FAILED);

        $this->page->expects($this->once())
            ->method('setPaymentMethods')
            ->with($this->allowedFallbackPaymentMethods);

        $this->sut->handle($this->event);
    }

    
}
