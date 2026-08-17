<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Model\AxytosOrderStateInfo;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\PluginOrderInterface;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\PluginOrderSupportsPartialRefundInterface;
use Axytos\KaufAufRechnung\Shopware\Adapter\HashCalculation\HashCalculator;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\BasketUpdateInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\CancelInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\CheckoutInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\InvoiceInformation;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\PartialRefundInformation;
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
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PluginOrder implements PluginOrderInterface, PluginOrderSupportsPartialRefundInterface
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
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        InvoiceOrderContext $invoiceOrderContext,
        OrderEntityRepository $orderEntityRepository,
        OrderStateMachine $orderStateMachine,
        HashCalculator $hashCalculator,
        SystemConfigService $systemConfigService
    ) {
        $this->invoiceOrderContext = $invoiceOrderContext;
        $this->orderEntityRepository = $orderEntityRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->hashCalculator = $hashCalculator;
        $this->systemConfigService = $systemConfigService;
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
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $orderEntity = $this->orderEntityRepository->findOrder($orderId, $context);

        $deliveryState = $this->getDeliveryStateTechnicalName($orderEntity);

        return OrderDeliveryStates::STATE_RETURNED === $deliveryState;
    }

    /**
     * @return bool
     */
    public function hasBeenPartialRefunded()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $orderEntity = $this->orderEntityRepository->findOrder($orderId, $context);

        $deliveryState = $this->getDeliveryStateTechnicalName($orderEntity);

        if (OrderDeliveryStates::STATE_PARTIALLY_RETURNED === $deliveryState) {
            if ($this->hasDocumentOfType('credit_note')) {
                return true;
            }

            if ($this->hasRefundFlaggedLineItem($orderEntity)) {
                return true;
            }
            if ($this->hasRefundInformationJSON($orderEntity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    public function saveHasRefundReported()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        $attributes->setRefundReported(true);

        $this->orderEntityRepository->updateAxytosOrderAttributes($orderId, $attributes, $context);

        $this->orderStateMachine->refundOrder($orderId, $context);
    }

    /**
     * @return bool
     */
    public function hasRefundReported()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        return $attributes->isRefundReported();
    }

    /**
     * @return void
     */
    /**
     * @return bool
     */
    public function savePartialRefundReported()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $orderEntity = $this->orderEntityRepository->findOrder($orderId, $context);

        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        $lastReportedAtRaw = $attributes->getPartialRefundLastReportedAt();
        $lastReportedAt = $lastReportedAtRaw instanceof \DateTimeInterface
            ? $lastReportedAtRaw
            : null;

        $reportedQuantitiesDelta = $this->buildReportedPartialRefundQuantities($orderEntity, $lastReportedAt);

        $existingPositions = $attributes->getPartialRefundPositions();
        if (!is_array($existingPositions)) {
            $existingPositions = [];
        }

        $cumulativePositions = $this->mergePartialRefundPositionsAsTotal($existingPositions, $reportedQuantitiesDelta);

        $attributes->setPartialRefundLastReportedAt(new \DateTimeImmutable());
        $attributes->setPartialRefundPositions(
            $this->normalizePartialRefundPositions($cumulativePositions)
        );
        $this->orderEntityRepository->updateAxytosOrderAttributes($orderId, $attributes, $context);

        if ($this->isOrderFullyRefunded($orderEntity, $cumulativePositions)) {
            $this->orderStateMachine->refundOrder($orderId, $context);

            return false;
        }
        $this->orderStateMachine->refundOrderPartially($orderId, $context);

        return true;
    }

    public function getPartialRefundLastReportedAt(): ?\DateTimeInterface
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($orderId, $context);

        return $attributes->getPartialRefundLastReportedAt();
    }

    /**
     * @return array<int, array{
     *   lineItemId: string,
     *   sku: string|null,
     *   quantity: int,
     *   source: 'custom_column'|'returnInformation',
     *   ReturnDate?: string|null
     * }>
     */
    private function buildReportedPartialRefundQuantities(OrderEntity $orderEntity, ?\DateTimeInterface $lastReportedAt): array
    {
        $refundColumn = $this->getRefundColumnNameForOrder($orderEntity);

        if (null !== $refundColumn && $this->hasRefundFlaggedLineItem($orderEntity)) {
            return $this->buildQuantitiesFromRefundColumn($orderEntity, $refundColumn);
        }

        if ($this->hasRefundInformationJSON($orderEntity)) {
            return $this->buildTotalQuantitiesFromReturnInformation($orderEntity);
        }

        return [];
    }

    /**
     * @return array<int, array{lineItemId: string, sku: string|null, quantity: int, source: 'custom_column'}>
     */
    private function buildQuantitiesFromRefundColumn(OrderEntity $orderEntity, string $refundColumn): array
    {
        $result = [];

        foreach ($orderEntity->getLineItems() ?? [] as $lineItem) {
            /** @var OrderLineItemEntity $lineItem */
            $customFields = $lineItem->getCustomFields() ?? [];

            $qty = $this->toInt($customFields[$refundColumn] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $orderedQty = $lineItem->getQuantity();

            $payload = $lineItem->getPayload() ?? [];

            $sku = null;
            if (isset($payload['productNumber']) && \is_string($payload['productNumber']) && '' !== $payload['productNumber']) {
                $sku = $payload['productNumber'];
            } elseif (\is_string($lineItem->getReferencedId()) && '' !== $lineItem->getReferencedId()) {
                $sku = $lineItem->getReferencedId();
            }

            $result[] = [
                'lineItemId' => $lineItem->getId(),
                'sku' => $sku,
                'quantity' => \min($qty, $orderedQty),
                'source' => 'custom_column',
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{
     *   lineItemId: string,
     *   sku: string|null,
     *   quantity: int,
     *   source: 'returnInformation',
     *   ReturnDate?: string|null
     * }>
     */
    private function buildTotalQuantitiesFromReturnInformation(OrderEntity $orderEntity): array
    {
        $result = [];
        $tz = new \DateTimeZone('UTC');

        foreach ($orderEntity->getLineItems() ?? [] as $lineItem) {
            /** @var OrderLineItemEntity $lineItem */
            $customFields = $lineItem->getCustomFields() ?? [];
            $returnInfo = $customFields['returnInformation'] ?? null;

            if (\is_string($returnInfo) && '' !== $returnInfo) {
                $decoded = \json_decode($returnInfo, true);
                if (JSON_ERROR_NONE === \json_last_error()) {
                    $returnInfo = $decoded;
                }
            }

            if (!\is_array($returnInfo) || [] === $returnInfo) {
                continue;
            }

            $totalQty = 0;
            $maxReturnDate = null;

            foreach ($returnInfo as $row) {
                if (!\is_array($row)) {
                    continue;
                }

                $q = $this->toInt($row['quantity'] ?? 0);
                if ($q <= 0) {
                    continue;
                }

                $totalQty += $q;

                $dt = $this->parseReturnDateToUtc($row['returnDate'] ?? null, $tz);
                if (null !== $dt && (null === $maxReturnDate || $dt > $maxReturnDate)) {
                    $maxReturnDate = $dt;
                }
            }

            if ($totalQty <= 0) {
                continue;
            }

            $orderedQty = $lineItem->getQuantity();
            $totalQty = \min($totalQty, $orderedQty);

            $payload = $lineItem->getPayload() ?? [];
            $sku = null;

            if (isset($payload['productNumber']) && \is_string($payload['productNumber']) && '' !== $payload['productNumber']) {
                $sku = $payload['productNumber'];
            } elseif (isset($payload['product_number']) && \is_string($payload['product_number']) && '' !== $payload['product_number']) {
                $sku = $payload['product_number'];
            } else {
                $ref = $lineItem->getReferencedId();
                if (\is_string($ref) && '' !== $ref) {
                    $sku = $ref;
                } else {
                    $id = $lineItem->getIdentifier();
                    $sku = ('' !== $id) ? $id : null;
                }
            }

            $result[] = [
                'lineItemId' => $lineItem->getId(),
                'sku' => $sku,
                'quantity' => $totalQty,
                'source' => 'returnInformation',
                'ReturnDate' => null !== $maxReturnDate ? $maxReturnDate->format('c') : null,
            ];
        }

        return $result;
    }

    private function parseReturnDateToUtc(mixed $raw, \DateTimeZone $tz): ?\DateTimeImmutable
    {
        if (!\is_string($raw) || '' === $raw) {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', $raw, $tz);
        if (false === $dt) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $raw, $tz);
        }

        if (false === $dt) {
            try {
                $dt = new \DateTimeImmutable($raw, $tz);
            } catch (\Throwable) {
                return null;
            }
        }

        return $dt;
    }

    /**
     * @return bool
     */
    public function hasNewPartialRefundSinceLastReport()
    {
        $orderId = $this->invoiceOrderContext->getOrderId();
        $context = $this->invoiceOrderContext->getContext();
        $orderEntity = $this->orderEntityRepository->findOrder($orderId, $context);

        $lastReportedAt = $this->getPartialRefundLastReportedAt();

        $deliveryState = $this->getDeliveryStateTechnicalName($orderEntity);

        if (OrderDeliveryStates::STATE_PARTIALLY_RETURNED !== $deliveryState) {
            return false;
        }

        $hasNewCreditNotes = $this->hasNewCreditNotesSince($orderEntity, $lastReportedAt);
        $hasNewRefundLineItems = $this->hasNewRefundFlaggedLineItemsSince($orderEntity, $lastReportedAt);
        $hasNewReturnInfo = $this->hasNewReturnInformationSince($orderEntity, $lastReportedAt);

        return $hasNewCreditNotes || $hasNewRefundLineItems || $hasNewReturnInfo;
    }

    private function hasNewCreditNotesSince(OrderEntity $orderEntity, ?\DateTimeInterface $since): bool
    {
        $documents = $orderEntity->getDocuments();

        if (null === $documents || 0 === $documents->count()) {
            return false;
        }

        /** @var DocumentEntity $document */
        foreach ($documents as $document) {
            $type = $document->getDocumentType();
            if (null === $type || 'credit_note' !== $type->getTechnicalName()) {
                continue;
            }

            $created = $document->getCreatedAt();
            if (null === $created) {
                continue;
            }

            if (null === $since || $created > $since) {
                return true;
            }
        }

        return false;
    }

    private function hasNewRefundFlaggedLineItemsSince(OrderEntity $orderEntity, ?\DateTimeInterface $lastReportedAt): bool
    {
        $lineItems = $orderEntity->getLineItems();

        if (null === $lineItems || 0 === $lineItems->count()) {
            return false;
        }

        $fieldName = $this->getRefundColumnNameForOrder($orderEntity);
        if (null === $fieldName) {
            return false;
        }

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItems as $lineItem) {
            if (!$this->isLineItemMarkedForRefund($lineItem, $fieldName)) {
                continue;
            }

            $changedAt = $lineItem->getUpdatedAt() ?? $lineItem->getCreatedAt();

            if (null === $lastReportedAt) {
                return true;
            }

            if (null !== $changedAt && $changedAt > $lastReportedAt) {
                return true;
            }
        }

        return false;
    }

    private function hasNewReturnInformationSince(OrderEntity $orderEntity, ?\DateTimeInterface $lastReportedAt): bool
    {
        $lineItems = $orderEntity->getLineItems();
        if (null === $lineItems || 0 === $lineItems->count()) {
            return false;
        }

        $thresholdTs = null !== $lastReportedAt ? $lastReportedAt->getTimestamp() : null;
        $tz = new \DateTimeZone('UTC');

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItems as $lineItem) {
            $customFields = $lineItem->getCustomFields() ?? [];
            $returnInfo = $customFields['returnInformation'] ?? null;

            if (\is_string($returnInfo) && '' !== $returnInfo) {
                $decoded = \json_decode($returnInfo, true);
                if (JSON_ERROR_NONE === \json_last_error()) {
                    $returnInfo = $decoded;
                }
            }

            if (!\is_array($returnInfo) || [] === $returnInfo) {
                continue;
            }

            foreach ($returnInfo as $row) {
                if (!\is_array($row)) {
                    continue;
                }

                $qty = $this->toInt($row['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                if (null === $thresholdTs) {
                    return true;
                }

                $dt = $this->parseReturnDateToUtc($row['returnDate'] ?? null, $tz);
                if (null !== $dt && $dt->getTimestamp() > $thresholdTs) {
                    return true;
                }
            }
        }

        return false;
    }

    public function refundInformation()
    {
        return new RefundInformation($this->invoiceOrderContext);
    }

    public function partialRefundInformation()
    {
        return new PartialRefundInformation($this->invoiceOrderContext);
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

    private function getDeliveryStateTechnicalName(OrderEntity $orderEntity): ?string
    {
        $deliveries = $orderEntity->getDeliveries();

        if (null === $deliveries || 0 === $deliveries->count()) {
            return null;
        }

        $delivery = $deliveries->first();
        $state = null !== $delivery ? $delivery->getStateMachineState() : null;

        return null !== $state ? $state->getTechnicalName() : null;
    }

    private function getRefundColumnNameForOrder(OrderEntity $orderEntity): ?string
    {
        $salesChannelId = $orderEntity->getSalesChannelId();

        $columnName = $this->systemConfigService->get(
            'AxytosKaufAufRechnung.config.refundColumn',
            $salesChannelId
        );

        if (!\is_string($columnName)) {
            return null;
        }

        $columnName = \trim($columnName);

        return '' !== $columnName ? $columnName : null;
    }

    private function hasRefundFlaggedLineItem(OrderEntity $orderEntity): bool
    {
        $lineItems = $orderEntity->getLineItems();

        if (null === $lineItems || 0 === $lineItems->count()) {
            return false;
        }

        $fieldName = $this->getRefundColumnNameForOrder($orderEntity);
        if (null === $fieldName) {
            return false;
        }

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItems as $lineItem) {
            if ($this->isLineItemMarkedForRefund($lineItem, $fieldName)) {
                return true;
            }
        }

        return false;
    }

    private function hasRefundInformationJSON(OrderEntity $orderEntity): bool
    {
        foreach ($orderEntity->getLineItems() ?? [] as $lineItem) {
            /** @var OrderLineItemEntity $lineItem */
            $customFields = $lineItem->getCustomFields();

            if (is_array($customFields) && \array_key_exists('returnInformation', $customFields)) {
                return true;
            }
        }

        return false;
    }

    private function isLineItemMarkedForRefund(OrderLineItemEntity $lineItem, string $fieldName): bool
    {
        $value = $this->getLineItemFieldValue($lineItem, $fieldName);

        if (null === $value) {
            return false;
        }

        return $value > 0;
    }

    private function getLineItemFieldValue(OrderLineItemEntity $lineItem, string $fieldName): mixed
    {
        $customFields = $lineItem->getCustomFields() ?? [];
        if (\array_key_exists($fieldName, $customFields)) {
            return $customFields[$fieldName];
        }

        $extensions = $lineItem->getExtensions();
        if (\array_key_exists($fieldName, $extensions)) {
            return $extensions[$fieldName];
        }

        if ($lineItem->has($fieldName)) {
            return $lineItem->get($fieldName);
        }

        return null;
    }

    /**
     * @param array<int,array<string,mixed>> $cumulativePositions
     */
    private function isOrderFullyRefunded(OrderEntity $orderEntity, array $cumulativePositions): bool
    {
        $refundedById = [];

        /** @var array<int, array<string, scalar|null>> $cumulativePositions */
        foreach ($cumulativePositions as $pos) {
            $rawId = $pos['lineItemId'] ?? null;

            if (!is_string($rawId) && !is_int($rawId)) {
                continue;
            }

            $id = is_string($rawId) ? trim($rawId) : (string) $rawId;
            if ('' === $id || '0' === $id) {
                continue;
            }

            $refundedById[$id] = $this->toInt($pos['quantity'] ?? null);
        }

        $lineItems = $orderEntity->getLineItems();
        if (null === $lineItems || 0 === $lineItems->count()) {
            return false;
        }

        foreach ($lineItems as $lineItem) {
            $lineItemId = $lineItem->getId();

            if ('' === $lineItemId) {
                continue;
            }

            $orderedQty = $lineItem->getQuantity();
            $refundedQty = ($refundedById[$lineItemId] ?? 0);

            if ($refundedQty < $orderedQty) {
                return false;
            }
        }

        return true;
    }

    /**
     * existing/currentTotals sind Arrays mit beliebigen Keys, aber mindestens:
     * - lineItemId: string|int (optional)
     * - quantity: int|string|float|null (optional)
     *
     * @param array<int, array<string, scalar|null>> $existing
     * @param array<int, array<string, scalar|null>> $currentTotals
     *
     * @return array<int, array<string, scalar|null>>
     */
    private function mergePartialRefundPositionsAsTotal(array $existing, array $currentTotals): array
    {
        /** @var array<string, array<string, scalar|null>> $byLineItemId */
        $byLineItemId = [];

        foreach ($existing as $pos) {
            $id = $this->lineItemIdFromPos($pos);
            if (null === $id) {
                continue;
            }

            $pos['lineItemId'] = $id;
            $pos['quantity'] = $this->toInt($pos['quantity'] ?? null);

            $byLineItemId[$id] = $pos;
        }

        foreach ($currentTotals as $pos) {
            $id = $this->lineItemIdFromPos($pos);
            if (null === $id) {
                continue;
            }

            $base = $byLineItemId[$id] ?? [];
            $merged = array_merge($base, $pos);

            $merged['lineItemId'] = $id;
            $merged['quantity'] = $this->toInt($pos['quantity'] ?? null);

            $byLineItemId[$id] = $merged;
        }

        return array_values($byLineItemId);
    }

    /**
     * @param array<string, scalar|null> $pos
     */
    private function lineItemIdFromPos(array $pos): ?string
    {
        $raw = $pos['lineItemId'] ?? null;

        if (is_string($raw)) {
            $id = trim($raw);

            return '' !== $id ? $id : null;
        }

        if (is_int($raw)) {
            return 0 !== $raw ? (string) $raw : null;
        }

        return null;
    }

    private function toInt(mixed $value): int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_float($value)) {
            return (int) $value;
        }

        if (\is_string($value)) {
            $v = trim($value);
            if ('' === $v || !is_numeric($v)) {
                return 0;
            }

            return (int) $v;
        }

        return 0;
    }

    /**
     * @param array<int, array<string, scalar|null>> $positions
     *
     * @return array<int, array{sku: string, quantity: int}>
     */
    private function normalizePartialRefundPositions(array $positions): array
    {
        /** @var array<int, array{sku: string, quantity: int}> $out */
        $out = [];

        foreach ($positions as $pos) {
            $skuRaw = $pos['sku'] ?? $pos['productNumber'] ?? $pos['productId'] ?? null;
            if (!is_string($skuRaw)) {
                continue;
            }

            $sku = trim($skuRaw);
            if ('' === $sku) {
                continue;
            }

            $qty = $this->toInt($pos['quantity'] ?? null);
            if ($qty <= 0) {
                continue;
            }

            $out[] = ['sku' => $sku, 'quantity' => $qty];
        }

        return $out;
    }
}
