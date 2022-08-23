<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\AccountEditOrderPage;

use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\Shopware\PaymentMethod\PaymentMethodCollectionFilter;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;

class AccountEditOrderPageLoadedEventHandler
{   
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;
    private PaymentMethodCollectionFilter $paymentMethodCollectionFilter;

    public function __construct(
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        PaymentMethodCollectionFilter $paymentMethodCollectionFilter)
    {
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

        if ($paymentControlOrderState === OrderCheckProcessStates::FAILED)
        {
            $paymentMethods = $page->getPaymentMethods();
            $paymentMethods = $this->paymentMethodCollectionFilter->filterAllowedFallbackPaymentMethods($paymentMethods);
            $page->setPaymentMethods($paymentMethods);
        }

        if ($paymentControlOrderState === OrderCheckProcessStates::CHECKED)
        {
            $paymentMethods = $page->getPaymentMethods();
            $paymentMethods = $this->paymentMethodCollectionFilter->filterPaymentMethodsNotUsingHandler($paymentMethods, AxytosInvoicePaymentHandler::class);
            $paymentMethods = $this->paymentMethodCollectionFilter->filterNotUnsafePaymentMethods($paymentMethods);
            $page->setPaymentMethods($paymentMethods);
        }
    }
}
