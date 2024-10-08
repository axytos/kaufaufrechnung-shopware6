<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Model\AxytosOrderStateInfo;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\PluginOrderInterface;
use Axytos\KaufAufRechnung\Shopware\Adapter\HashCalculation\HashCalculator;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\BasketUpdateInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\CancelInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\CheckoutInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\InvoiceInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\PaymentInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\RefundInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\ShippingInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\TrackingInformation;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\OrderStates;

class PluginOrder implements PluginOrderInterface
{
    /**
     * @var InvoiceOrderContext
     */
    private $invoiceOrderContext;

    /**
     * @var OrderEntityRepository
     */
    private $orderEntityRepository;

    /**
     * @var OrderStateMachine
     */
    private $orderStateMachine;

    /**
     * @var HashCalculator
     */
    private $hashCalculator;

    public function __construct(
        InvoiceOrderContext $invoiceOrderContext,
        OrderEntityRepository $orderEntityRepository,
        OrderStateMachine $orderStateMachine,
        HashCalculator $hashCalculator
    ) {
        $this->invoiceOrderContext = $invoiceOrderContext;
        $this->orderEntityRepository = $orderEntityRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->hashCalculator = $hashCalculator;
    }

    /**
     * @return string|int
     */
    public function getOrderNumber()
    {
        return $this->invoiceOrderContext->getOrderNumber();
    }

    /**
     * @return AxytosOrderStateInfo|null
     */
    public function loadState()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);
        $state = $attributes->getOrderState();
        $data = $attributes->getOrderStateData();

        return new AxytosOrderStateInfo($state, $data);
    }

    /**
     * @param string      $state
     * @param string|null $data
     *
     * @return void
     */
    public function saveState($state, $data = null)
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);
        $attributes->setOrderState($state);
        if (!is_null($data)) {
            $attributes->setOrderStateData($data);
        }
        $this->orderEntityRepository->updateAxytosOrderAttributes($orderId, $attributes, $context);
    }

    /**
     * @return void
     */
    public function freezeBasket()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        $hash = $this->calculateOrderBasketHash();
        $attributes->setOrderBasketHash($hash);

        $this->orderEntityRepository->updateAxytosOrderAttributes($orderId, $attributes, $context);
    }

    public function checkoutInformation()
    {
        return new CheckoutInformation($this->invoiceOrderContext);
    }

    /**
     * @return bool
     */
    public function hasBeenCanceled()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $orderEntity = $this->orderEntityRepository->findOrder($orderId, $context);
        $state = $orderEntity->getStateMachineState();

        return !is_null($state) && OrderStates::STATE_CANCELLED === $state->getTechnicalName();
    }

    public function cancelInformation()
    {
        return new CancelInformation($this->invoiceOrderContext);
    }

    /**
     * @return bool
     */
    public function hasBeenInvoiced()
    {
        // check if order status is completed
        // one order may have multiple invoices
        // when invoices are created with an ERP system and synced back to shopware we cannot know the final number of all invoices
        // so we assume that the order is completely invoiced when:
        // a) there is at least one invoice, because we need the number of the invoice
        // b) the order status is completed

        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $orderEntity = $this->orderEntityRepository->findOrder($orderId, $context);
        $state = $orderEntity->getStateMachineState();

        return $this->hasDocumentOfType('invoice')
            && !is_null($state)
            && OrderStates::STATE_COMPLETED === $state->getTechnicalName();
    }

    public function invoiceInformation()
    {
        return new InvoiceInformation($this->invoiceOrderContext);
    }

    /**
     * @return bool
     */
    public function hasBeenRefunded()
    {
        // disable refund detection for now
        // refund detection does not work reliably because the refund is neither always created in shopware nor synced back from ERP systems
        // need to discuss whether we need this feature or remove it
        return false;
    }

    public function refundInformation()
    {
        return new RefundInformation($this->invoiceOrderContext);
    }

    /**
     * @return bool
     */
    public function hasShippingReported()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        return $attributes->getShippingReported();
    }

    /**
     * @return bool
     */
    public function hasBeenShipped()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $orderEntity = $this->orderEntityRepository->findOrder($orderId, $context);
        $deliveries = $orderEntity->getDeliveries();

        if (is_null($deliveries)) {
            return false;
        }

        if (0 === $deliveries->count()) {
            return false;
        }

        /** @var array<OrderDeliveryEntity> */
        $orderDeliveryEntities = $deliveries->getElements();

        foreach ($orderDeliveryEntities as $orderDeliveryEntity) {
            $state = $orderDeliveryEntity->getStateMachineState();
            if (is_null($state) || OrderDeliveryStates::STATE_SHIPPED !== $state->getTechnicalName()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return void
     */
    public function saveHasShippingReported()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);
        $attributes->setShippingReported(true);
        $this->orderEntityRepository->updateAxytosOrderAttributes($orderId, $attributes, $context);
    }

    public function shippingInformation()
    {
        return new ShippingInformation($this->invoiceOrderContext);
    }

    /**
     * @return bool
     */
    public function hasNewTrackingInformation()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        $serializedTrackingIds = $this->serializedTrackingIds();
        $reportedTrackingCode = $attributes->getReportedTrackingCode();

        return $serializedTrackingIds !== $reportedTrackingCode;
    }

    /**
     * @return void
     */
    public function saveNewTrackingInformation()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);
        $attributes->setReportedTrackingCode($this->serializedTrackingIds());
        $this->orderEntityRepository->updateAxytosOrderAttributes($orderId, $attributes, $context);
    }

    /**
     * @return string
     */
    private function serializedTrackingIds()
    {
        $trackingInformation = $this->trackingInformation();
        $trackingIds = $trackingInformation->getTrackingIds();

        return serialize($trackingIds);
    }

    public function trackingInformation()
    {
        return new TrackingInformation($this->invoiceOrderContext);
    }

    /**
     * @return bool
     */
    public function hasBasketUpdates()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        $oldHash = $attributes->getOrderBasketHash();
        $newHash = $this->calculateOrderBasketHash();

        return $oldHash !== $newHash;
    }

    /**
     * @return void
     */
    public function saveBasketUpdatesReported()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        $hash = $this->calculateOrderBasketHash();
        $attributes->setOrderBasketHash($hash);

        $this->orderEntityRepository->updateAxytosOrderAttributes($orderId, $attributes, $context);
    }

    public function basketUpdateInformation()
    {
        return new BasketUpdateInformation($this->invoiceOrderContext);
    }

    /**
     * @return void
     */
    public function saveHasBeenPaid()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $this->orderStateMachine->payOrder($orderId, $context);
    }

    public function paymentInformation()
    {
        return new PaymentInformation($this->invoiceOrderContext);
    }

    /**
     * @return string
     */
    private function calculateOrderBasketHash()
    {
        $basket = $this->checkoutInformation()->getBasket();

        return $this->hashCalculator->calculateBasketHash($basket);
    }

    /**
     * @param string $documentTypeTechnicalName
     *
     * @return bool
     */
    private function hasDocumentOfType($documentTypeTechnicalName)
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $orderEntity = $this->orderEntityRepository->findOrder($orderId, $context);
        /** @var DocumentCollection|null */
        $documents = $orderEntity->getDocuments();

        if (!is_null($documents)) {
            /** @var DocumentEntity $document */
            foreach ($documents as $document) {
                $documentType = $document->getDocumentType();
                if (!is_null($documentType) && $documentType->getTechnicalName() === $documentTypeTechnicalName) {
                    return true;
                }
            }
        }

        return false;
    }
}
