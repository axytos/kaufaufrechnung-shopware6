<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Throwable;

class ReturnOrderEventsSubscriber implements EventSubscriberInterface
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
            'state_enter.order_delivery.state.returned' => 'onReturned',
        ];
    }

    public function onReturned(OrderStateMachineStateChangeEvent $event): void
    {
        try {
            if ($this->pluginConfigurationValidator->isInvalid()) {
                return;
            }

            if ($this->isNotConfirmedOrder($event)) {
                return;
            }

            $invoiceOrderContext = $this->getInvoiceOrderContext($event);
            $this->invoiceClient->return($invoiceOrderContext);
        } catch (Throwable $throwable) {
            $this->errorHandler->handle($throwable);
        }
    }

    private function isNotConfirmedOrder(OrderStateMachineStateChangeEvent $event): bool
    {
        $orderId = $event->getOrderId();
        $context = $event->getContext();
        $state = $this->orderCheckProcessStateMachine->getState($orderId, $context);
        return $state !== OrderCheckProcessStates::CONFIRMED;
    }

    private function getInvoiceOrderContext(OrderStateMachineStateChangeEvent $event): InvoiceOrderContextInterface
    {
        $orderId = $event->getOrderId();
        $context = $event->getContext();
        return $this->invoiceOrderContextFactory->getInvoiceOrderContext($orderId, $context);
    }
}
