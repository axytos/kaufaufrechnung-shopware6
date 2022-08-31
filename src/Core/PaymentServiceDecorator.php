<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\ShopActions;
use Axytos\Shopware\PaymentMethod\PaymentMethodPredicates;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\Shopware\Order\OrderStateMachine;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\Shopware\Routing\Router;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class PaymentServiceDecorator extends PaymentService
{
    private PaymentService $decorated;
    private PluginConfigurationValidator $pluginConfigurationValidator;
    private OrderStateMachine $orderStateMachine;
    private Router $router;
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;
    private PaymentMethodPredicates $paymentMethodPredicates;
    private ErrorHandler $errorHandler;
    private InvoiceClientInterface $invoiceClient;
    private InvoiceOrderContextFactory $invoiceOrderContextFactory;

    public function __construct(
        PaymentService $decorated,
        PluginConfigurationValidator $pluginConfigurationValidator,
        OrderStateMachine $orderStateMachine,
        Router $router,
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        PaymentMethodPredicates $paymentMethodPredicates,
        ErrorHandler $errorHandler,
        InvoiceClientInterface $invoiceClient,
        InvoiceOrderContextFactory $invoiceOrderContextFactory
    ) {
        $this->decorated = $decorated;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->orderStateMachine = $orderStateMachine;
        $this->router = $router;
        $this->orderCheckProcessStateMachine = $orderCheckProcessStateMachine;
        $this->paymentMethodPredicates = $paymentMethodPredicates;
        $this->errorHandler = $errorHandler;
        $this->invoiceClient = $invoiceClient;
        $this->invoiceOrderContextFactory = $invoiceOrderContextFactory;
    }

    public function handlePaymentByOrder(
        string $orderId,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        ?string $finishUrl = null,
        ?string $errorUrl = null
    ): ?RedirectResponse {
        try {
            if ($this->pluginConfigurationValidator->isInvalid()) {
                return $this->completeOrder($orderId, $dataBag, $context, $finishUrl, $errorUrl);
            }

            if ($this->usesAxytosInvoicePaymentMethod($context)) {
                return $this->executeAxytosInvoice($orderId, $dataBag, $context, $finishUrl, $errorUrl);
            }

            return $this->completeOrder($orderId, $dataBag, $context, $finishUrl, $errorUrl);
        } catch (Throwable $t) {
            $this->orderCheckProcessStateMachine->setFailed($orderId, $context);
            $this->errorHandler->handle($t);
            return $this->completeOrder($orderId, $dataBag, $context, $finishUrl, $errorUrl);
        }
    }

    private function usesAxytosInvoicePaymentMethod(SalesChannelContext $context): bool
    {
        $paymentMethod = $context->getPaymentMethod();
        return $this->paymentMethodPredicates->usesHandler($paymentMethod, AxytosInvoicePaymentHandler::class);
    }

    private function executeAxytosInvoice(
        string $orderId,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        ?string $finishUrl = null,
        ?string $errorUrl = null
    ): ?RedirectResponse {
        $this->orderCheckProcessStateMachine->setUnchecked($orderId, $context);

        $invoiceOrderContext = $this->invoiceOrderContextFactory->getInvoiceOrderContext($orderId, $context->getContext());
        $action = $this->invoiceClient->precheck($invoiceOrderContext);

        $this->orderCheckProcessStateMachine->setChecked($orderId, $context);

        if ($action === ShopActions::CHANGE_PAYMENT_METHOD) {
            return $this->changePaymentMethodWithError($orderId, $context);
        }

        $this->invoiceClient->confirmOrder($invoiceOrderContext);
        $this->orderCheckProcessStateMachine->setConfirmed($orderId, $context);

        return $this->completeOrder($orderId, $dataBag, $context, $finishUrl, $errorUrl);
    }

    private function changePaymentMethodWithError(string $orderId, SalesChannelContext $context): RedirectResponse
    {
        $this->orderStateMachine->failPayment($orderId, $context);
        return $this->router->redirectToEditOrderPageWithError($orderId);
    }

    private function completeOrder(
        string $orderId,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        ?string $finishUrl = null,
        ?string $errorUrl = null
    ): ?RedirectResponse {
        return $this->decorated->handlePaymentByOrder($orderId, $dataBag, $context, $finishUrl, $errorUrl);
    }

    public function finalizeTransaction(string $paymentToken, Request $request, SalesChannelContext $context): TokenStruct
    {
        return $this->decorated->finalizeTransaction($paymentToken, $request, $context);
    }
}
