<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Throwable;

class ShippingOrderEventsSubscriber implements EventSubscriberInterface
{
    private ErrorHandler $errorHandler;
    private InvoiceClientInterface $invoiceClient;
    private InvoiceOrderContextFactory $invoiceOrderContextFactory;
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;
    private PluginConfigurationValidator $pluginConfigurationValidator;

    public function __construct(
        ErrorHandler $errorHandler,
        InvoiceClientInterface $invoiceClient,
        InvoiceOrderContextFactory $invoiceOrderContextFactory,
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        PluginConfigurationValidator $pluginConfigurationValidator
    ) {
        $this->errorHandler = $errorHandler;
        $this->invoiceClient = $invoiceClient;
        $this->invoiceOrderContextFactory = $invoiceOrderContextFactory;
        $this->orderCheckProcessStateMachine = $orderCheckProcessStateMachine;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_delivery.state.shipped' => 'onShipped',
        ];
    }

    public function onShipped(OrderStateMachineStateChangeEvent $event): void
    {
        try {
            if ($this->pluginConfigurationValidator->isInvalid()) {
                return;
            }

            $orderId = $event->getOrderId();
            $context = $event->getContext();

            $orderState = $this->orderCheckProcessStateMachine->getState($orderId, $context);
            if ($orderState === OrderCheckProcessStates::CONFIRMED) {
                $orderContext = $this->invoiceOrderContextFactory->getInvoiceOrderContext($orderId, $context);
                $this->invoiceClient->reportShipping($orderContext);
            }
        } catch (Throwable $t) {
            $this->errorHandler->handle($t);
        }
    }
}
