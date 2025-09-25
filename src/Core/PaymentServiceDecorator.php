<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\ECommerce\Clients\Invoice\ShopActions;
use Axytos\KaufAufRechnung\Core\Model\AxytosOrderFactory;
use Axytos\KaufAufRechnung\Shopware\Adapter\PluginOrderFactory;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodPredicates;
use Axytos\KaufAufRechnung\Shopware\Routing\Router;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PaymentServiceDecorator extends PaymentService
{
    /**
     * @var PaymentService
     */
    private $decorated;
    /**
     * @var PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var OrderStateMachine
     */
    private $orderStateMachine;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var OrderCheckProcessStateMachine
     */
    private $orderCheckProcessStateMachine;
    /**
     * @var PaymentMethodPredicates
     */
    private $paymentMethodPredicates;
    /**
     * @var ErrorHandler
     */
    private $errorHandler;
    /**
     * @var PluginOrderFactory
     */
    private $pluginOrderFactory;
    /**
     * @var AxytosOrderFactory
     */
    private $axytosOrderFactory;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        PaymentService $decorated,
        SystemConfigService $systemConfigService,
        PluginConfigurationValidator $pluginConfigurationValidator,
        OrderStateMachine $orderStateMachine,
        Router $router,
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        PaymentMethodPredicates $paymentMethodPredicates,
        ErrorHandler $errorHandler,
        PluginOrderFactory $pluginOrderFactory,
        AxytosOrderFactory $axytosOrderFactory
    ) {
        $this->decorated = $decorated;
        $this->systemConfigService = $systemConfigService;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->orderStateMachine = $orderStateMachine;
        $this->router = $router;
        $this->orderCheckProcessStateMachine = $orderCheckProcessStateMachine;
        $this->paymentMethodPredicates = $paymentMethodPredicates;
        $this->errorHandler = $errorHandler;
        $this->pluginOrderFactory = $pluginOrderFactory;
        $this->axytosOrderFactory = $axytosOrderFactory;
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
        } catch (\Throwable $t) {
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
        $pluginOrder = $this->pluginOrderFactory->create($orderId, $context->getContext());
        $axytosOrder = $this->axytosOrderFactory->create($pluginOrder);
        $skipPrecheck = $this->getPrecheckCheckoutRule($context);
        $axytosOrder->checkout($skipPrecheck);

        $action = $axytosOrder->getOrderCheckoutAction();
        if (ShopActions::CHANGE_PAYMENT_METHOD === $action) {
            return $this->changePaymentMethodWithError($orderId, $context);
        }

        $this->orderStateMachine->setConfiguredAfterCheckoutOrderStatus($orderId, $context->getContext());
        $this->orderStateMachine->setConfiguredAfterCheckoutPaymentStatus($orderId, $context->getContext());

        return $this->completeOrder($orderId, $dataBag, $context, $finishUrl, $errorUrl);
    }

    private function changePaymentMethodWithError(string $orderId, SalesChannelContext $context): RedirectResponse
    {
        $this->orderStateMachine->failPayment($orderId, $context->getContext());

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

    public function getPrecheckCheckoutRule(SalesChannelContext $context): bool
    {
        /** @var string $ruleId */
        $ruleId = $this->systemConfigService->get('AxytosKaufAufRechnung.config.precheckControl', $context->getSalesChannelId());
        if (null === $ruleId || !Uuid::isValid($ruleId)) {
            return false;
        }

        return in_array($ruleId, $context->getRuleIds(), true);
    }
}
