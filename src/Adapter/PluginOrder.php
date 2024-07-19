<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\PluginOrderInterface;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Model\AxytosOrderStateInfo;
use Axytos\KaufAufRechnung\Shopware\Adapter\HashCalculation\HashCalculator;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\BasketUpdateInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\CancelInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\CheckoutInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\InvoiceInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\PaymentInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\RefundInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\ShippingInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\TrackingInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\Refund\BasketFactory as RefundBasketFactory;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use OpenSearch\Endpoints\Tasks\Cancel;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\OrderStates;

class PluginOrder implements PluginOrderInterface
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext
     */
    private $invoiceOrderContext;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository
     */
    private $orderEntityRepository;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine
     */
    private $orderStateMachine;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Adapter\HashCalculation\HashCalculator
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
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Model\AxytosOrderStateInfo|null
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
     * @param string $state
     * @param string|null $data
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

        return !is_null($state) && $state->getTechnicalName() === OrderStates::STATE_CANCELLED;
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
        return $this->hasDocumentOfType('invoice');
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
        return $this->hasDocumentOfType('credit_note');
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
        return $this->hasDocumentOfType('delivery_note');
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
