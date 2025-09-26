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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AxytosInvoicePaymentHandler extends AbstractPaymentHandler
{
    /**
     * @var PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var PluginOrderFactory
     */
    private $pluginOrderFactory;
    /**
     * @var AxytosOrderFactory
     */
    private $axytosOrderFactory;
    /**
     * @var ErrorHandler
     */
    private $errorHandler;
    /**
     * @var OrderCheckProcessStateMachine
     */
    private $orderCheckProcessStateMachine;
    /**
     * @var OrderStateMachine
     */
    private $orderStateMachine;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var EntityRepository<OrderTransactionCollection>
     */
    private $orderTransactionRepository;
    /**
     * @var PaymentMethodPredicates
     */
    private $paymentMethodPredicates;
    /**
     * @var EntityRepository<PaymentMethodCollection>
     */
    private $paymentMethodRepository;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public const NAME = 'Kauf auf Rechnung';
    public const DESCRIPTION = 'Sie zahlen bequem die Rechnung, sobald Sie die Ware erhalten haben, innerhalb der Zahlfrist';
    public const TECHNICAL_NAME = 'payment_axytos_kaufaufrechnung';

    /**
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     * @param EntityRepository<PaymentMethodCollection>    $paymentMethodRepository
     */
    public function __construct(
        ErrorHandler $errorHandler,
        SystemConfigService $systemConfigService,
        Router $router,
        PluginOrderFactory $pluginOrderFactory,
        AxytosOrderFactory $axytosOrderFactory,
        EntityRepository $orderTransactionRepository,
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        OrderStateMachine $orderStateMachine,
        PluginConfigurationValidator $pluginConfigurationValidator,
        PaymentMethodPredicates $paymentMethodPredicates,
        EntityRepository $paymentMethodRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->errorHandler = $errorHandler;
        $this->pluginOrderFactory = $pluginOrderFactory;
        $this->axytosOrderFactory = $axytosOrderFactory;
        $this->router = $router;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->orderCheckProcessStateMachine = $orderCheckProcessStateMachine;
        $this->orderStateMachine = $orderStateMachine;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->paymentMethodPredicates = $paymentMethodPredicates;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        $orderTransaction = $this->getOrderFromOrderTransaction($transaction, $context);
        $orderId = $orderTransaction->getOrderId();
        $order = $orderTransaction->getOrder();
        $salesChannelId = $order?->getSalesChannelId();
        $paymentMethodId = $orderTransaction->getPaymentMethodId();

        try {
            if ($this->pluginConfigurationValidator->isInvalid()) {
                return $this->completePayment($orderId);
            }
            if ($this->usesAxytosInvoicePaymentMethod($paymentMethodId, $context)) {
                return $this->executeAxytosInvoice($orderId, $salesChannelId, $context);
            }

            return $this->completePayment($orderId);
        } catch (\Throwable $t) {
            $this->orderCheckProcessStateMachine->setFailed($orderId, $context);
            $this->errorHandler->handle($t);

            return new RedirectResponse('/checkout/finish?orderId={orderId}');
        }
    }

    private function executeAxytosInvoice(
        string $orderId,
        ?string $salesChannelId,
        Context $context,
    ): RedirectResponse {
        $pluginOrder = $this->pluginOrderFactory->create($orderId, $context);
        $axytosOrder = $this->axytosOrderFactory->create($pluginOrder);
        $skipPrecheck = $this->getPrecheckCheckoutRule($salesChannelId, $context);
        $axytosOrder->checkout($skipPrecheck);

        $action = $axytosOrder->getOrderCheckoutAction();
        if (ShopActions::CHANGE_PAYMENT_METHOD === $action) {
            return $this->changePaymentMethodWithError($orderId, $context);
        }

        $this->orderStateMachine->setConfiguredAfterCheckoutOrderStatus($orderId, $context);
        $this->orderStateMachine->setConfiguredAfterCheckoutPaymentStatus($orderId, $context);

        return $this->completePayment($orderId);
    }

    private function changePaymentMethodWithError(string $orderId, Context $context): RedirectResponse
    {
        $this->orderStateMachine->failPayment($orderId, $context);

        return $this->router->redirectToEditOrderPageWithError($orderId);
    }

    public function completePayment(string $orderId): RedirectResponse
    {
        return new RedirectResponse('/checkout/finish?orderId=' . urlencode($orderId));
    }

    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void
    {
    }

    private function usesAxytosInvoicePaymentMethod(string $paymentMethodId, Context $context): bool
    {
        $paymentMethod = $this->getPaymentMethod($paymentMethodId, $context);

        return $this->paymentMethodPredicates->usesHandler($paymentMethod, AxytosInvoicePaymentHandler::class);
    }

    public function getOrderFromOrderTransaction(PaymentTransactionStruct $transaction, Context $context): OrderTransactionEntity
    {
        $transactionId = $transaction->getOrderTransactionId();

        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order');

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if (!$orderTransaction instanceof OrderTransactionEntity) {
            throw new \RuntimeException('Order transaction not found.');
        }

        return $orderTransaction;
    }

    public function getPaymentMethod(string $paymentMethodId, Context $context): PaymentMethodEntity
    {
        /** @var PaymentMethodEntity|null paymentMethodEntity */
        $paymentMethodEntity = $this->paymentMethodRepository->search(new Criteria([$paymentMethodId]), $context)->first();

        if (!$paymentMethodEntity instanceof PaymentMethodEntity) {
            throw new \RuntimeException('PaymentMethod not found.');
        }

        return $paymentMethodEntity;
    }

    public function getPrecheckCheckoutRule(?string $salesChannelId, Context $context): bool
    {
        /** @var string $ruleId */
        $ruleId = $this->systemConfigService->get('AxytosKaufAufRechnung.config.precheckControl', $salesChannelId);
        if (null === $ruleId || !Uuid::isValid($ruleId)) {
            return false;
        }

        return in_array($ruleId, $context->getRuleIds(), true);
    }
}
