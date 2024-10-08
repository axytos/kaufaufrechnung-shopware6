<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\AccountEditOrderPage;

use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\KaufAufRechnung\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodCollectionFilter;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;

class AccountEditOrderPageLoadedEventHandler
{
    /**
     * @var OrderCheckProcessStateMachine
     */
    private $orderCheckProcessStateMachine;
    /**
     * @var PaymentMethodCollectionFilter
     */
    private $paymentMethodCollectionFilter;

    public function __construct(
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        PaymentMethodCollectionFilter $paymentMethodCollectionFilter
    ) {
        $this->orderCheckProcessStateMachine = $orderCheckProcessStateMachine;
        $this->paymentMethodCollectionFilter = $paymentMethodCollectionFilter;
    }

    public function handle(AccountEditOrderPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $context = $event->getContext();

        $order = $page->getOrder();
        $orderId = $order->getId();

        $paymentControlOrderState = $this->orderCheckProcessStateMachine->getState($orderId, $context);

        if (OrderCheckProcessStates::FAILED === $paymentControlOrderState) {
            $paymentMethods = $page->getPaymentMethods();
            $paymentMethods = $this->paymentMethodCollectionFilter->filterAllowedFallbackPaymentMethods($paymentMethods);
            $page->setPaymentMethods($paymentMethods);
        }

        if (OrderCheckProcessStates::CHECKED === $paymentControlOrderState) {
            $paymentMethods = $page->getPaymentMethods();
            $paymentMethods = $this->paymentMethodCollectionFilter->filterPaymentMethodsNotUsingHandler($paymentMethods, AxytosInvoicePaymentHandler::class);
            $paymentMethods = $this->paymentMethodCollectionFilter->filterNotUnsafePaymentMethods($paymentMethods);
            $page->setPaymentMethods($paymentMethods);
        }
    }
}
