<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDtoCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class RefundBasketDtoFactory
{
    /**
     * @var RefundBasketPositionDtoCollectionFactory
     */
    private $refundBasketPositionDtoCollectionFactory;

    /**
     * @var RefundBasketTaxGroupDtoCollectionFactory
     */
    private $refundBasketTaxGroupDtoCollectionFactory;

    public function __construct(
        RefundBasketPositionDtoCollectionFactory $refundBasketPositionDtoCollectionFactory,
        RefundBasketTaxGroupDtoCollectionFactory $refundBasketTaxGroupDtoCollectionFactory
    ) {
        $this->refundBasketPositionDtoCollectionFactory = $refundBasketPositionDtoCollectionFactory;
        $this->refundBasketTaxGroupDtoCollectionFactory = $refundBasketTaxGroupDtoCollectionFactory;
    }

    /**
     * @param array<string,int> $alreadyRefundedTotalsBySku
     */
    public function create(OrderEntity $orderEntity, array $alreadyRefundedTotalsBySku = []): RefundBasketDto
    {
        if ([] === $alreadyRefundedTotalsBySku) {
            $refundBasket = new RefundBasketDto();
            $refundBasket->grossTotal = $orderEntity->getAmountTotal();
            $refundBasket->netTotal = $orderEntity->getAmountNet();
            $refundBasket->positions = $this->refundBasketPositionDtoCollectionFactory->create($orderEntity->getLineItems());
            $refundBasket->taxGroups = $this->refundBasketTaxGroupDtoCollectionFactory->create($orderEntity->getPrice()->getCalculatedTaxes());

            return $refundBasket;
        }

        $lineItems = $orderEntity->getLineItems() ?? new OrderLineItemCollection();
        $remainingLineItems = new OrderLineItemCollection();

        $sawAnyRefundMarker = false;
        $changedSomething = false;

        /** @var OrderLineItemEntity $li */
        foreach ($lineItems as $li) {
            if ('product' !== $li->getType()) {
                continue;
            }

            $sku = $this->resolveSku($li);
            if (null === $sku) {
                continue;
            }

            $orderedQty = (int) $li->getQuantity();
            if ($orderedQty <= 0) {
                continue;
            }

            if (!array_key_exists($sku, $alreadyRefundedTotalsBySku)) {
                $remainingLineItems->add($li);
                continue;
            }

            $sawAnyRefundMarker = true;

            $already = $alreadyRefundedTotalsBySku[$sku];

            if ($already <= 0) {
                $remainingLineItems->add($li);
                continue;
            }

            $remainingQty = $orderedQty - $already;

            if ($remainingQty <= 0) {
                $changedSomething = true;
                continue;
            }

            $changedSomething = true;

            $temp = clone $li;
            $temp->setQuantity($remainingQty);

            $price = $temp->getPrice();
            if ($price instanceof CalculatedPrice) {
                $temp->setPrice($this->scalePriceForQuantity($price, $remainingQty));
            }

            $remainingLineItems->add($temp);
        }

        if (!$sawAnyRefundMarker || !$changedSomething) {
            $refundBasket = new RefundBasketDto();
            $refundBasket->grossTotal = $orderEntity->getAmountTotal();
            $refundBasket->netTotal = $orderEntity->getAmountNet();
            $refundBasket->positions = $this->refundBasketPositionDtoCollectionFactory->create($orderEntity->getLineItems());
            $refundBasket->taxGroups = $this->refundBasketTaxGroupDtoCollectionFactory->create($orderEntity->getPrice()->getCalculatedTaxes());

            return $refundBasket;
        }

        if (0 === $remainingLineItems->count()) {
            $refundBasket = new RefundBasketDto();
            $refundBasket->grossTotal = 0.0;
            $refundBasket->netTotal = 0.0;
            $refundBasket->positions = new RefundBasketPositionDtoCollection();
            $refundBasket->taxGroups = $this->refundBasketTaxGroupDtoCollectionFactory->create(new CalculatedTaxCollection());

            return $refundBasket;
        }

        $grossTotal = 0.0;
        $netTotal = 0.0;
        $positions = [];

        /** @var OrderLineItemEntity $li */
        foreach ($remainingLineItems as $li) {
            $price = $li->getPrice();
            if (null === $price) {
                continue;
            }

            $gross = (float) $price->getTotalPrice();
            $tax = (float) $price->getCalculatedTaxes()->getAmount();
            $net = $gross - $tax;

            $grossTotal += $gross;
            $netTotal += $net;

            $pos = new RefundBasketPositionDto();
            $pos->productId = $this->resolveSku($li);
            $pos->grossRefundTotal = $gross;
            $pos->netRefundTotal = $net;

            $positions[] = $pos;
        }

        $refundBasket = new RefundBasketDto();
        $refundBasket->grossTotal = $grossTotal;
        $refundBasket->netTotal = $netTotal;
        $refundBasket->positions = new RefundBasketPositionDtoCollection(...$positions);

        $taxes = $this->collectTaxesForLineItems($remainingLineItems);
        $refundBasket->taxGroups = $this->refundBasketTaxGroupDtoCollectionFactory->create($taxes);

        return $refundBasket;
    }

    private function resolveSku(OrderLineItemEntity $lineItem): ?string
    {
        $payload = $lineItem->getPayload() ?? [];

        $sku =
            $payload['productNumber']
            ?? $payload['product_number']
            ?? $lineItem->getReferencedId()
            ?? $lineItem->getIdentifier();

        return is_string($sku) && '' !== trim($sku) ? $sku : null;
    }

    private function collectTaxesForLineItems(OrderLineItemCollection $lineItems): CalculatedTaxCollection
    {
        $allTaxes = new CalculatedTaxCollection();

        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItems as $lineItem) {
            $price = $lineItem->getPrice();
            if (null === $price) {
                continue;
            }

            foreach ($price->getCalculatedTaxes() as $tax) {
                $allTaxes->add($tax);
            }
        }

        return $allTaxes;
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
}
