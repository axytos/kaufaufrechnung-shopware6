<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDtoCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class RefundPartialBasketDtoFactory
{
    /** @var RefundBasketTaxGroupDtoCollectionFactory */
    private $refundBasketTaxGroupDtoCollectionFactory;

    public function __construct(
        RefundBasketTaxGroupDtoCollectionFactory $refundBasketTaxGroupDtoCollectionFactory
    ) {
        $this->refundBasketTaxGroupDtoCollectionFactory = $refundBasketTaxGroupDtoCollectionFactory;
    }

    /**
     * @param array<string,int> $alreadyRefundedBySku
     */
    public function create(
        OrderEntity $orderEntity,
        array $alreadyRefundedBySku = [],
        ?\DateTimeInterface $lastReportedAt = null
    ): RefundBasketDto {
        $latestCreditNote = $this->getLatestCreditNoteDocument($orderEntity);

        if (null !== $latestCreditNote) {
            return $this->createRefundBasketFromCreditNote($latestCreditNote);
        }

        return $this->createRefundBasketFromField($orderEntity, $alreadyRefundedBySku, $lastReportedAt);
    }

    /**
     * @param array<string,int> $alreadyRefundedBySku
     */
    private function createRefundBasketFromField(
        OrderEntity $orderEntity,
        array $alreadyRefundedBySku,
        ?\DateTimeInterface $lastReportedAt
    ): RefundBasketDto {
        $refundableLineItems = $this->hasRefundInformationJSON($orderEntity)
            ? $this->getRefundableLineItemsFromJSON($orderEntity, $lastReportedAt)
            : $this->getRefundableLineItemsFromMap($orderEntity, $alreadyRefundedBySku);

        $grossTotal = 0.0;
        $netTotal = 0.0;

        $positions = [];

        foreach ($refundableLineItems as $lineItem) {
            $price = $lineItem->getPrice();
            if (!$price instanceof CalculatedPrice) {
                continue;
            }

            $grossTotalLineItem = $price->getTotalPrice();
            $taxAmount = $price->getCalculatedTaxes()->getAmount();
            $net = $grossTotalLineItem - $taxAmount;

            $grossTotal += $grossTotalLineItem;
            $netTotal += $net;

            $payload = $lineItem->getPayload() ?? [];

            $productIdRaw =
                $payload['productNumber']
                ?? $payload['product_number']
                ?? $lineItem->getReferencedId()
                ?? $lineItem->getIdentifier();

            $productId = $this->stringOrNull($productIdRaw);

            $pos = new RefundBasketPositionDto();
            $pos->productId = $productId;
            $pos->grossRefundTotal = $grossTotalLineItem;
            $pos->netRefundTotal = $net;

            $positions[] = $pos;
        }

        $refundBasket = new RefundBasketDto();
        $refundBasket->grossTotal = $grossTotal;
        $refundBasket->netTotal = $netTotal;
        $refundBasket->positions = new RefundBasketPositionDtoCollection(...$positions);

        $calculatedTaxes = $this->collectTaxesForLineItems($refundableLineItems);
        $refundBasket->taxGroups = $this->refundBasketTaxGroupDtoCollectionFactory->create($calculatedTaxes);

        return $refundBasket;
    }

    private function createRefundBasketFromCreditNote(DocumentEntity $creditNote): RefundBasketDto
    {
        $rawItems = $this->getCreditNoteLineItems($creditNote);

        $positions = [];
        $grossTotal = 0.0;
        $netTotal = 0.0;

        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = $this->stringOrNull($item['productId'] ?? $item['productNumber'] ?? $item['sku'] ?? null);

            $gross = $this->floatOrNull($item['totalPrice'] ?? $item['gross'] ?? $item['total'] ?? null) ?? 0.0;
            $net = $this->floatOrNull($item['totalNetPrice'] ?? $item['net'] ?? null);

            if (null === $net) {
                $tax = $this->floatOrNull($item['tax'] ?? $item['taxAmount'] ?? null);
                $net = null !== $tax ? max(0.0, $gross - $tax) : 0.0;
            }

            if (0.0 === $gross && 0.0 === $net && null === $productId) {
                continue;
            }

            $pos = new RefundBasketPositionDto();
            $pos->productId = $productId;
            $pos->grossRefundTotal = $gross;
            $pos->netRefundTotal = $net;

            $positions[] = $pos;

            $grossTotal += $gross;
            $netTotal += $net;
        }

        $refundBasket = new RefundBasketDto();
        $refundBasket->grossTotal = $grossTotal;
        $refundBasket->netTotal = $netTotal;
        $refundBasket->positions = new RefundBasketPositionDtoCollection(...$positions);

        $refundBasket->taxGroups = $this->refundBasketTaxGroupDtoCollectionFactory->create(new CalculatedTaxCollection());

        return $refundBasket;
    }

    private function getLatestCreditNoteDocument(OrderEntity $order): ?DocumentEntity
    {
        $documents = $order->getDocuments();
        if (null === $documents || 0 === $documents->count()) {
            return null;
        }

        $latest = null;
        $latestTs = null;

        /** @var DocumentEntity $doc */
        foreach ($documents as $doc) {
            $type = $doc->getDocumentType();
            if (null === $type || 'credit_note' !== $type->getTechnicalName()) {
                continue;
            }

            $dt = $doc->getCreatedAt() ?? $doc->getUpdatedAt();
            $ts = $dt instanceof \DateTimeInterface ? $dt->getTimestamp() : null;

            if (null === $latest) {
                $latest = $doc;
                $latestTs = $ts;
                continue;
            }

            if (null !== $ts && (null === $latestTs || $ts > $latestTs)) {
                $latest = $doc;
                $latestTs = $ts;
            }
        }

        return $latest;
    }

    /**
     * @param array<string,int> $alreadyRefundedBySku
     */
    private function getRefundableLineItemsFromMap(OrderEntity $orderEntity, array $alreadyRefundedBySku): OrderLineItemCollection
    {
        $lineItems = $orderEntity->getLineItems() ?? new OrderLineItemCollection();
        $filtered = new OrderLineItemCollection();

        foreach ($lineItems as $lineItem) {
            $price = $lineItem->getPrice();
            if (!$price instanceof CalculatedPrice) {
                continue;
            }

            $payload = $lineItem->getPayload() ?? [];
            $skuRaw =
                $payload['productNumber']
                ?? $payload['product_number']
                ?? $lineItem->getReferencedId()
                ?? $lineItem->getIdentifier();

            $sku = $this->stringOrNull($skuRaw);
            if (null === $sku) {
                continue;
            }

            $refundedQty = $alreadyRefundedBySku[$sku] ?? 0;
            if ($refundedQty <= 0) {
                continue;
            }

            $origQty = $lineItem->getQuantity();
            $newQty = min($refundedQty, $origQty);

            if ($newQty <= 0) {
                continue;
            }

            $tempLineItem = clone $lineItem;
            $tempLineItem->setQuantity($newQty);
            $tempLineItem->setPrice($this->scalePriceForQuantity($price, $newQty));

            $filtered->add($tempLineItem);
        }

        return $filtered;
    }

    private function getRefundableLineItemsFromJSON(
        OrderEntity $orderEntity,
        ?\DateTimeInterface $lastReportedAt
    ): OrderLineItemCollection {
        $lineItems = $orderEntity->getLineItems() ?? new OrderLineItemCollection();
        $filtered = new OrderLineItemCollection();

        $threshold = $lastReportedAt instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($lastReportedAt)
            : new \DateTimeImmutable('@0');

        foreach ($lineItems as $lineItem) {
            /** @var OrderLineItemEntity $lineItem */
            $customFields = $lineItem->getCustomFields() ?? [];
            $returnInfoRaw = $customFields['returnInformation'] ?? null;

            $returnInfo = $returnInfoRaw;
            if (is_string($returnInfo) && '' !== $returnInfo) {
                $decoded = json_decode($returnInfo, true);
                if (JSON_ERROR_NONE === json_last_error()) {
                    $returnInfo = $decoded;
                }
            }

            if (!is_array($returnInfo) || [] === $returnInfo) {
                continue;
            }

            $returnedQty = 0;

            foreach ($returnInfo as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $returnDateRaw = $row['returnDate'] ?? null;
                if (!is_string($returnDateRaw) || '' === $returnDateRaw) {
                    continue;
                }

                $returnDate = \DateTimeImmutable::createFromFormat(
                    'Y-m-d\TH:i:s.u',
                    $returnDateRaw,
                    new \DateTimeZone('UTC')
                );

                if (false === $returnDate) {
                    $returnDate = \DateTimeImmutable::createFromFormat(
                        'Y-m-d\TH:i:s',
                        $returnDateRaw,
                        new \DateTimeZone('UTC')
                    );
                }

                if (false === $returnDate || $returnDate <= $threshold) {
                    continue;
                }

                $returnedQty += (int) ($row['quantity'] ?? 0);
            }

            if ($returnedQty <= 0) {
                continue;
            }

            $price = $lineItem->getPrice();
            if (!$price instanceof CalculatedPrice) {
                continue;
            }

            $newQty = min($returnedQty, $lineItem->getQuantity());
            if ($newQty <= 0) {
                continue;
            }

            $tempLineItem = clone $lineItem;
            $tempLineItem->setQuantity($newQty);
            $tempLineItem->setPrice($this->scalePriceForQuantity($price, $newQty));

            $filtered->add($tempLineItem);
        }

        return $filtered;
    }

    private function scalePriceForQuantity(CalculatedPrice $price, int $newQty): CalculatedPrice
    {
        $oldQty = $price->getQuantity();
        if ($oldQty <= 0 || $newQty <= 0 || $newQty === $oldQty) {
            return $price;
        }

        $unit = $price->getUnitPrice();
        $newTotal = round($unit * $newQty, 2);

        $factor = $newQty / $oldQty;

        $oldTaxes = $price->getCalculatedTaxes();
        $newTaxes = new CalculatedTaxCollection();

        $taxSum = 0.0;
        $i = 0;
        $count = $oldTaxes->count();

        foreach ($oldTaxes as $tax) {
            ++$i;

            $taxAmount = round($tax->getTax() * $factor, 2);
            $taxPrice = round($tax->getPrice() * $factor, 2);

            if ($i === $count) {
                $taxAmount = round(($newTotal - ($newTotal - ($taxSum + $taxAmount))) - $taxSum, 2);
            }

            $taxSum += $taxAmount;

            $newTaxes->add(new CalculatedTax(
                $taxAmount,
                $tax->getTaxRate(),
                $taxPrice
            ));
        }

        $newUnit = round($newTotal / $newQty, 2);

        return new CalculatedPrice(
            $newUnit,
            $newTotal,
            $newTaxes,
            $price->getTaxRules(),
            $newQty
        );
    }

    private function collectTaxesForLineItems(OrderLineItemCollection $lineItems): CalculatedTaxCollection
    {
        $allTaxes = new CalculatedTaxCollection();

        foreach ($lineItems as $lineItem) {
            $price = $lineItem->getPrice();
            if (!$price instanceof CalculatedPrice) {
                continue;
            }

            foreach ($price->getCalculatedTaxes() as $tax) {
                $allTaxes->add($tax);
            }
        }

        return $allTaxes;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function getCreditNoteLineItems(DocumentEntity $document): array
    {
        $config = $document->getConfig();

        $custom = $config['custom'] ?? null;
        if (!is_array($custom)) {
            return [];
        }

        $lineItems = $custom['lineItems'] ?? null;
        if (!is_array($lineItems)) {
            return [];
        }

        /** @var list<array<string,mixed>> $lineItems */
        return $lineItems;
    }

    private function hasRefundInformationJSON(OrderEntity $order): bool
    {
        foreach ($order->getLineItems() ?? [] as $lineItem) {
            $customFields = $lineItem->getCustomFields() ?? [];
            if (array_key_exists('returnInformation', $customFields)) {
                return true;
            }
        }

        return false;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);

        return '' !== $value ? $value : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ('' === $value) {
                return null;
            }

            $value = str_replace(',', '.', $value);
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}
