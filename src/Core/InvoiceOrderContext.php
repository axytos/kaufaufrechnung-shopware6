<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface;
use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
use Axytos\ECommerce\DataTransferObjects\RefundBasketDto;
use Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\KaufAufRechnung\Shopware\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CustomerDataDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\DeliveryAddressDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\InvoiceAddressDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundPartialBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\LogisticianCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\TrackingIdCalculator;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class InvoiceOrderContext implements InvoiceOrderContextInterface
{
    /** @var string */
    private $orderId;
    /** @var Context */
    private $context;

    /** @var OrderEntityRepository */
    private $orderEntityRepository;

    /** @var CustomerDataDtoFactory */
    private $customerDataDtoFactory;
    /** @var DeliveryAddressDtoFactory */
    private $deliveryAddressDtoFactory;
    /** @var InvoiceAddressDtoFactory */
    private $invoiceAddressDtoFactory;

    /** @var BasketDtoFactory */
    private $basketDtoFactory;
    /** @var CreateInvoiceBasketDtoFactory */
    private $createInvoiceBasketDtoFactory;

    /** @var RefundBasketDtoFactory */
    private $refundBasketDtoFactory;
    /** @var RefundPartialBasketDtoFactory */
    private $refundPartialBasketDtoFactory;

    /** @var DtoToDtoMapper */
    private $dtoToDtoMapper;
    /** @var ReturnPositionModelDtoCollectionFactory */
    private $returnPositionModelDtoCollectionFactory;

    /** @var TrackingIdCalculator */
    private $trackingIdCalculator;
    /** @var LogisticianCalculator */
    private $logisticianCalculator;

    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(
        string $orderId,
        Context $context,
        OrderEntityRepository $orderEntityRepository,
        CustomerDataDtoFactory $customerDataDtoFactory,
        DeliveryAddressDtoFactory $deliveryAddressDtoFactory,
        InvoiceAddressDtoFactory $invoiceAddressDtoFactory,
        BasketDtoFactory $basketDtoFactory,
        CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory,
        RefundBasketDtoFactory $refundBasketDtoFactory,
        RefundPartialBasketDtoFactory $refundPartialBasketDtoFactory,
        DtoToDtoMapper $dtoToDtoMapper,
        ReturnPositionModelDtoCollectionFactory $returnPositionModelDtoCollectionFactory,
        TrackingIdCalculator $trackingIdCalculator,
        LogisticianCalculator $logisticianCalculator,
        SystemConfigService $systemConfigService
    ) {
        $this->orderId = $orderId;
        $this->context = $context;

        $this->orderEntityRepository = $orderEntityRepository;

        $this->customerDataDtoFactory = $customerDataDtoFactory;
        $this->deliveryAddressDtoFactory = $deliveryAddressDtoFactory;
        $this->invoiceAddressDtoFactory = $invoiceAddressDtoFactory;

        $this->basketDtoFactory = $basketDtoFactory;
        $this->createInvoiceBasketDtoFactory = $createInvoiceBasketDtoFactory;

        $this->refundBasketDtoFactory = $refundBasketDtoFactory;
        $this->refundPartialBasketDtoFactory = $refundPartialBasketDtoFactory;

        $this->dtoToDtoMapper = $dtoToDtoMapper;
        $this->returnPositionModelDtoCollectionFactory = $returnPositionModelDtoCollectionFactory;

        $this->trackingIdCalculator = $trackingIdCalculator;
        $this->logisticianCalculator = $logisticianCalculator;

        $this->systemConfigService = $systemConfigService;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    private function getOrder(): OrderEntity
    {
        return $this->orderEntityRepository->findOrder($this->orderId, $this->context);
    }

    public function getOrderNumber(): string
    {
        $order = $this->getOrder();
        $orderNumber = $order->getOrderNumber();

        if (null === $orderNumber) {
            throw new \RuntimeException("OrderNumber not defined for order with id '{$order->getId()}'.");
        }

        return $orderNumber;
    }

    public function getOrderInvoiceNumber(): string
    {
        $order = $this->getOrder();
        $documents = $order->getDocuments();

        if (null === $documents) {
            return '';
        }

        /** @var DocumentEntity $document */
        foreach ($documents as $document) {
            $documentType = $document->getDocumentType();
            if (null !== $documentType && 'invoice' === $documentType->getTechnicalName()) {
                $config = $document->getConfig();

                if (is_array($config) && isset($config['documentNumber']) && is_scalar($config['documentNumber'])) {
                    return (string) $config['documentNumber'];
                }

                return '';
            }
        }

        return '';
    }

    public function getOrderDateTime(): \DateTimeInterface
    {
        return $this->getOrder()->getOrderDateTime();
    }

    public function getPersonalData()
    {
        return $this->customerDataDtoFactory->create($this->getOrder());
    }

    public function getInvoiceAddress()
    {
        return $this->invoiceAddressDtoFactory->create($this->getOrder());
    }

    public function getDeliveryAddress()
    {
        return $this->deliveryAddressDtoFactory->create($this->getOrder());
    }

    public function getBasket()
    {
        return $this->basketDtoFactory->create($this->getOrder());
    }

    public function getCreateInvoiceBasket()
    {
        return $this->createInvoiceBasketDtoFactory->create($this->getOrder());
    }

    public function getShippingBasketPositions(): ShippingBasketPositionDtoCollection
    {
        $basketPositions = $this->getBasket()->positions;

        return $this->dtoToDtoMapper->mapDtoCollection(
            $basketPositions,
            ShippingBasketPositionDtoCollection::class
        );
    }

    public function getPreCheckResponseData(): array
    {
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($this->orderId, $this->context);

        return $attributes->getOrderPreCheckResult();
    }

    public function setPreCheckResponseData($data): void
    {
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($this->orderId, $this->context);
        $attributes->setOrderPreCheckResult($data);

        $this->orderEntityRepository->updateAxytosOrderAttributes($this->orderId, $attributes, $this->context);
    }

    public function getRefundBasket()
    {
        $order = $this->getOrder();

        $refundColumn = $this->getRefundColumnName($order);

        // Refund-Column aktiv -> Attributes ignorieren
        if (null !== $refundColumn) {
            $alreadyRefundedBySku = $this->buildAlreadyRefundedTotalsBySkuFromRefundColumn($order, $refundColumn);

            return $this->refundBasketDtoFactory->create($order, $alreadyRefundedBySku);
        }

        // Fallback: klassische Attributes
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($this->orderId, $this->context);
        $alreadyRefundedBySku = $this->buildAlreadyRefundedTotalsBySkuFromAttributes($attributes);

        return $this->refundBasketDtoFactory->create($order, $alreadyRefundedBySku);
    }

    /**
     * @return RefundBasketDto
     */
    public function getPartialRefundBasket()
    {
        $order = $this->getOrder();

        $refundColumn = $this->getRefundColumnName($order);

        $alreadyRefundedBySku = [];
        if (null !== $refundColumn) {
            $alreadyRefundedBySku = $this->buildAlreadyRefundedTotalsBySkuFromRefundColumn($order, $refundColumn);
        }

        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($this->orderId, $this->context);
        /** @var \DateTimeInterface|null $lastReportedAt */
        $lastReportedAt = $attributes->getPartialRefundLastReportedAt();

        return $this->refundPartialBasketDtoFactory->create(
            $order,
            $alreadyRefundedBySku,
            $lastReportedAt
        );
    }

    public function getReturnPositions()
    {
        return $this->returnPositionModelDtoCollectionFactory->create(
            $this->getOrder()->getLineItems()
        );
    }

    public function getDeliveryWeight(): float
    {
        // (wie bei dir) aktuell nicht relevant
        return 0.0;
    }

    public function getTrackingIds(): array
    {
        return $this->trackingIdCalculator->calculate($this->getOrder());
    }

    public function getLogistician(): string
    {
        return $this->logisticianCalculator->calculate($this->getOrder());
    }

    // --------------------
    // Refund helpers
    // --------------------

    private function getRefundColumnName(OrderEntity $orderEntity): ?string
    {
        $salesChannelId = $orderEntity->getSalesChannelId();

        $columnName = $this->systemConfigService->get(
            'AxytosKaufAufRechnung.config.refundColumn',
            $salesChannelId
        );

        if (!is_string($columnName)) {
            return null;
        }

        $columnName = trim($columnName);

        return '' !== $columnName ? $columnName : null;
    }

    /**
     * @param mixed $attributes
     *
     * @return array<string,int>
     */
    private function buildAlreadyRefundedTotalsBySkuFromAttributes($attributes): array
    {
        $map = [];

        if (!is_object($attributes) || !method_exists($attributes, 'getPartialRefundPositions')) {
            return $map;
        }

        /** @var mixed $positions */
        $positions = $attributes->getPartialRefundPositions();

        // falls JSON-String:
        if (is_string($positions) && '' !== $positions) {
            $decoded = json_decode($positions, true);
            if (JSON_ERROR_NONE === json_last_error()) {
                $positions = $decoded;
            }
        }

        if (!is_array($positions)) {
            return $map;
        }

        foreach ($positions as $p) {
            if (!is_array($p)) {
                continue;
            }

            $sku = $p['sku'] ?? null;
            $qty = (int) ($p['quantity'] ?? 0);

            if (is_string($sku) && '' !== $sku && $qty > 0) {
                $map[$sku] = $qty;
            }
        }

        return $map;
    }

    /**
     * @return array<string,int>
     */
    private function buildAlreadyRefundedTotalsBySkuFromRefundColumn(OrderEntity $orderEntity, string $refundColumn): array
    {
        $totals = [];

        foreach ($this->buildQuantitiesFromRefundColumn($orderEntity, $refundColumn) as $row) {
            $sku = $row['sku'] ?? null;
            $qty = (int) ($row['quantity'] ?? 0);

            if (!is_string($sku) || '' === $sku || $qty <= 0) {
                continue;
            }

            $totals[$sku] = ($totals[$sku] ?? 0) + $qty;
        }

        return $totals;
    }

    /**
     * @return array<int,array{lineItemId:string,sku:string,quantity:int,source:string}>
     */
    private function buildQuantitiesFromRefundColumn(OrderEntity $orderEntity, string $refundColumn): array
    {
        $result = [];

        foreach ($orderEntity->getLineItems() ?? [] as $lineItem) {
            /** @var OrderLineItemEntity $lineItem */
            $customFields = $lineItem->getCustomFields() ?? [];
            $payload = $lineItem->getPayload() ?? [];

            // qty aus CustomField (mixed) -> int normalisieren
            $qtyRaw = $customFields[$refundColumn] ?? null;

            if (is_int($qtyRaw)) {
                $qty = $qtyRaw;
            } elseif (is_numeric($qtyRaw)) {
                $qty = (int) $qtyRaw;
            } else {
                $qty = 0;
            }

            if ($qty <= 0) {
                continue;
            }

            $orderedQty = $lineItem->getQuantity();
            if ($orderedQty <= 0) {
                continue;
            }

            $sku =
                $payload['productNumber']
                ?? $payload['product_number']
                ?? $lineItem->getReferencedId()
                ?? $lineItem->getIdentifier();

            if (!is_string($sku) || '' === trim($sku)) {
                continue;
            }

            $result[] = [
                'lineItemId' => $lineItem->getId(),
                'sku' => $sku,
                'quantity' => min($qty, $orderedQty), // int
                'source' => 'refund_column',
            ];
        }

        return $result;
    }
}
