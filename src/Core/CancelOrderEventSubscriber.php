<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class CancelOrderEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface
     */
    private $invoiceClient;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler
     */
    private $errorHandler;
    /**
     * @var \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Order\OrderCheckProcessStateMachine
     */
    private $orderCheckProcessStateMachine;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory
     */
    private $invoiceOrderContextFactory;

    public function __construct(
        InvoiceClientInterface $invoiceClient,
        ErrorHandler $errorHandler,
        PluginConfigurationValidator $pluginConfigurationValidator,
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        InvoiceOrderContextFactory $invoiceOrderContextFactory
    ) {
        $this->invoiceClient = $invoiceClient;
        $this->errorHandler = $errorHandler;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->orderCheckProcessStateMachine = $orderCheckProcessStateMachine;
        $this->invoiceOrderContextFactory = $invoiceOrderContextFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order.state.cancelled' => 'onOrderStateCancelled',
        ];
    }

    public function onOrderStateCancelled(OrderStateMachineStateChangeEvent $event): void
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
                $this->invoiceClient->cancelOrder($orderContext);
            }
        } catch (Throwable $t) {
            $this->errorHandler->handle($t);
        }
    }
}
