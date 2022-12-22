<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\ShopActions;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodPredicates;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\KaufAufRechnung\Shopware\Core\PaymentServiceDecorator;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung\Shopware\Routing\Router;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PaymentServiceDecoratorTest extends TestCase
{
    /** @var PaymentService&MockObject */
    private $decorated;

    /** @var PluginConfigurationValidator&MockObject */
    private $pluginConfigurationValidator;

    /** @var OrderStateMachine&MockObject */
    private $orderStateMachine;

    /** @var Router&MockObject */
    private $router;

    /** @var PaymentMethodPredicates&MockObject */
    private $paymentMethodPredicates;

    /** @var OrderCheckProcessStateMachine&MockObject */
    private $orderCheckProcessStateMachine;

    /** @var ErrorHandler&MockObject */
    private $errorHandler;

    /** @var InvoiceClientInterface&MockObject */
    private $invoiceClient;

    /** @var InvoiceOrderContextFactory&MockObject */
    private $invoiceOrderContextFactory;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Core\PaymentServiceDecorator
     */
    private $sut;

    private const ORDER_ID = 'orderId';
    private const FINISH_URL = 'finishUrl';
    private const ERROR_URL = 'errorUrl';

    /** @var RequestDataBag&MockObject */
    private $requestDataBag;

    /** @var SalesChannelContext&MockObject */
    private $salesChannelContext;

    /** @var PaymentMethodEntity&MockObject */
    private $paymentMethod;

    /** @var RedirectResponse&MockObject */
    private $completeOrderResponse;

    /** @var RedirectResponse&MockObject */
    private $cancelOrderResponse;

    /** @var RedirectResponse&MockObject */
    private $changePaymentMethodResponse;

    /** @var RedirectResponse&MockObject */
    private $changePaymentMethodWithErrorResponse;

    /** @var InvoiceOrderContext&MockObject */
    private $invoiceOrderContext;

    public function setUp(): void
    {
        $this->decorated = $this->createMock(PaymentService::class);
        $this->pluginConfigurationValidator = $this->createMock(PluginConfigurationValidator::class);
        $this->orderStateMachine = $this->createMock(OrderStateMachine::class);
        $this->router = $this->createMock(Router::class);
        $this->orderCheckProcessStateMachine = $this->createMock(OrderCheckProcessStateMachine::class);
        $this->paymentMethodPredicates = $this->createMock(PaymentMethodPredicates::class);
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->invoiceClient = $this->createMock(InvoiceClientInterface::class);
        $this->invoiceOrderContextFactory = $this->createMock(InvoiceOrderContextFactory::class);

        $this->sut = new PaymentServiceDecorator(
            $this->decorated,
            $this->pluginConfigurationValidator,
            $this->orderStateMachine,
            $this->router,
            $this->orderCheckProcessStateMachine,
            $this->paymentMethodPredicates,
            $this->errorHandler,
            $this->invoiceClient,
            $this->invoiceOrderContextFactory
        );

        $this->requestDataBag = $this->createMock(RequestDataBag::class);
        $this->salesChannelContext = $this->createMock(SalesChannelContext::class);
        $this->paymentMethod = $this->createMock(PaymentMethodEntity::class);

        $this->completeOrderResponse = $this->createMock(RedirectResponse::class);
        $this->cancelOrderResponse = $this->createMock(RedirectResponse::class);
        $this->changePaymentMethodResponse = $this->createMock(RedirectResponse::class);
        $this->changePaymentMethodWithErrorResponse = $this->createMock(RedirectResponse::class);

        $this->invoiceOrderContext = $this->createMock(InvoiceOrderContext::class);

        $this->setUpResponses();
    }

    private function setUpResponses(): void
    {
        $this->decorated
            ->method('handlePaymentByOrder')
            ->with(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL)
            ->willReturn($this->completeOrderResponse);

        $this->router
            ->method('redirectToCheckoutFailedPage')
            ->willReturn($this->cancelOrderResponse);

        $this->router
            ->method('redirectToEditOrderPage')
            ->with(self::ORDER_ID)
            ->willReturn($this->changePaymentMethodResponse);

        $this->router
            ->method('redirectToEditOrderPageWithError')
            ->with(self::ORDER_ID)
            ->willReturn($this->changePaymentMethodWithErrorResponse);
    }

    private function setUpAxytosInvoicPaymentMethodUsed(bool $used): void
    {
        $this->salesChannelContext
            ->method('getPaymentMethod')
            ->willReturn($this->paymentMethod);

        $this->paymentMethodPredicates
            ->method('usesHandler')
            ->with($this->paymentMethod, AxytosInvoicePaymentHandler::class)
            ->willReturn($used);
    }

    private function setUpInvoicePrecheckShopAction(string $shopAction): void
    {
        $this->salesChannelContext
            ->method('getContext')
            ->willReturn($this->createMock(Context::class));

        $this->invoiceOrderContextFactory
            ->method('getInvoiceOrderContext')
            ->with(self::ORDER_ID, $this->salesChannelContext->getContext())
            ->willReturn($this->invoiceOrderContext);

        $this->invoiceClient
            ->method('precheck')
            ->with($this->invoiceOrderContext)
            ->willReturn($shopAction);
    }

    public function test_handlePaymentByOrder_invoice_sets_order_unchecked(): void
    {
        $this->setUpAxytosInvoicPaymentMethodUsed(true);

        $this->orderCheckProcessStateMachine
            ->expects($this->once())
            ->method('setUnchecked')
            ->with(self::ORDER_ID, $this->salesChannelContext);

        $this->sut->handlePaymentByOrder(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL);
    }

    public function test_handlePaymentByOrder_invoice_sets_order_checked(): void
    {
        $this->setUpAxytosInvoicPaymentMethodUsed(true);

        $this->orderCheckProcessStateMachine
            ->expects($this->once())
            ->method('setChecked')
            ->with(self::ORDER_ID, $this->salesChannelContext);

        $this->sut->handlePaymentByOrder(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL);
    }

    public function test_hanldePaymentByOrder_invoice_sets_order_confirmed_if_action_is_to_complete_order(): void
    {
        $this->setUpAxytosInvoicPaymentMethodUsed(true);
        $this->setUpInvoicePrecheckShopAction(ShopActions::COMPLETE_ORDER);

        $this->orderCheckProcessStateMachine
            ->expects($this->once())
            ->method('setConfirmed')
            ->with(self::ORDER_ID, $this->salesChannelContext);

        $this->sut->handlePaymentByOrder(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL);
    }

    public function test_hanldePaymentByOrder_invoice_confirms_order_if_action_is_to_complete_order(): void
    {
        $this->setUpAxytosInvoicPaymentMethodUsed(true);
        $this->setUpInvoicePrecheckShopAction(ShopActions::COMPLETE_ORDER);

        $this->invoiceClient
            ->expects($this->once())
            ->method('confirmOrder')
            ->with($this->invoiceOrderContext);

        $this->sut->handlePaymentByOrder(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL);
    }

    public function test_hanldePaymentByOrder_invoice_delegates_to_decorated_if_action_is_to_complete_order(): void
    {
        $this->setUpAxytosInvoicPaymentMethodUsed(true);
        $this->setUpInvoicePrecheckShopAction(ShopActions::COMPLETE_ORDER);

        $actual = $this->sut->handlePaymentByOrder(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL);

        $this->assertSame($this->completeOrderResponse, $actual);
    }

    public function test_hanldePaymentByOrder_invoice_does_not_set_order_confirmed_if_action_is_to_change_payment_method(): void
    {
        $this->setUpAxytosInvoicPaymentMethodUsed(true);
        $this->setUpInvoicePrecheckShopAction(ShopActions::CHANGE_PAYMENT_METHOD);

        $this->orderCheckProcessStateMachine
            ->expects($this->never())
            ->method('setConfirmed')
            ->with(self::ORDER_ID, $this->salesChannelContext);

        $this->sut->handlePaymentByOrder(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL);
    }

    public function test_handlePaymentByOrder_invoice_fails_payment_if_action_is_to_change_payment_method(): void
    {
        $this->setUpAxytosInvoicPaymentMethodUsed(true);
        $this->setUpInvoicePrecheckShopAction(ShopActions::CHANGE_PAYMENT_METHOD);

        $this->orderStateMachine
            ->expects($this->once())
            ->method('failPayment')
            ->with(self::ORDER_ID, $this->salesChannelContext);

        $this->sut->handlePaymentByOrder(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL);
    }

    public function test_handlePaymentByOrder_invoice_redirects_to_edit_order_page_if_action_is_to_change_payment_method(): void
    {
        $this->setUpAxytosInvoicPaymentMethodUsed(true);
        $this->setUpInvoicePrecheckShopAction(ShopActions::CHANGE_PAYMENT_METHOD);

        $actual = $this->sut->handlePaymentByOrder(self::ORDER_ID, $this->requestDataBag, $this->salesChannelContext, self::FINISH_URL, self::ERROR_URL);

        $this->assertSame($this->changePaymentMethodWithErrorResponse, $actual);
    }

    public function test_finalizeTransaction_delecates_to_decorated(): void
    {
        $paymentToken = 'paymentToken';
        $request = $this->createMock(Request::class);
        $tokenStruct = $this->createMock(TokenStruct::class);

        $this->decorated
            ->method('finalizeTransaction')
            ->with($paymentToken, $request, $this->salesChannelContext)
            ->willReturn($tokenStruct);

        $acutal = $this->sut->finalizeTransaction($paymentToken, $request, $this->salesChannelContext);

        $this->assertSame($tokenStruct, $acutal);
    }
}
