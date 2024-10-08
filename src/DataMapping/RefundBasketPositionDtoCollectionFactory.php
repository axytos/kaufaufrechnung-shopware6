<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDtoCollection;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPriceCalculator;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class RefundBasketPositionDtoCollectionFactory
{
    /**
     * @var RefundBasketPositionDtoFactory
     */
    private $refundBasketPositionDtoFactory;
    /**
     * @var PositionNetPriceCalculator
     */
    private $positionNetPriceCalculator;

    public function __construct(RefundBasketPositionDtoFactory $refundBasketPositionDtoFactory, PositionNetPriceCalculator $positionNetPriceCalculator)
    {
        $this->refundBasketPositionDtoFactory = $refundBasketPositionDtoFactory;
        $this->positionNetPriceCalculator = $positionNetPriceCalculator;
    }

    public function create(?OrderLineItemCollection $orderLineItems = null): RefundBasketPositionDtoCollection
    {
        if (is_null($orderLineItems)) {
            return new RefundBasketPositionDtoCollection();
        }

        $credits = $orderLineItems->filter(function (OrderLineItemEntity $orderLineItemEntity) {
            return LineItem::CREDIT_LINE_ITEM_TYPE === $orderLineItemEntity->getType();
        });

        $products = $orderLineItems->filter(function (OrderLineItemEntity $orderLineItemEntity) {
            return LineItem::PRODUCT_LINE_ITEM_TYPE === $orderLineItemEntity->getType();
        });

        $groupedCredits = $this->groupLineItemsByTaxRate($credits);

        $positions = [];

        foreach ($groupedCredits as $taxRate => $creditGroup) {
            $grossRefundTotal = $this->calculateGrossRefundTotal($creditGroup);
            $netRefundTotal = $this->calculateNetRefundTotal($creditGroup);
            $productNumber = $this->findProductNumberForTaxRate($products, (string) $taxRate);

            $position = $this->refundBasketPositionDtoFactory->create($productNumber, $grossRefundTotal, $netRefundTotal);

            array_push($positions, $position);
        }

        return new RefundBasketPositionDtoCollection(...$positions);
    }

    /**
     * @param array<OrderLineItemEntity> $orderLineItems
     */
    private function calculateGrossRefundTotal(array $orderLineItems): float
    {
        $grossPrices = array_map(function (OrderLineItemEntity $oli) {
            $price = $oli->getPrice();
            if (is_null($price)) {
                return 0;
            }
            $price->getTotalPrice();
        }, $orderLineItems);

        return (float) array_sum($grossPrices) * -1;
    }

    /**
     * @param array<OrderLineItemEntity> $orderLineItems
     */
    private function calculateNetRefundTotal(array $orderLineItems): float
    {
        $netPrices = array_map(function (OrderLineItemEntity $oli) {
            return $this->positionNetPriceCalculator->calculate($oli->getPrice());
        }, $orderLineItems);

        return (float) array_sum($netPrices) * -1;
    }

    /**
     * @return array<string,array<\Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity>>
     */
    private function groupLineItemsByTaxRate(OrderLineItemCollection $orderLineItems)
    {
        /** @var array<string,array<\Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity>> */
        return $orderLineItems->reduce(function (array $carry, OrderLineItemEntity $orderLineItemEntity) {
            $price = $orderLineItemEntity->getPrice();
            if (!is_null($price)) {
                $calculatedTax = $price->getCalculatedTaxes()->first();
                if (!is_null($calculatedTax)) {
                    $taxRate = $calculatedTax->getTaxRate();
                    $taxKey = "{$taxRate}";

                    $carry[$taxKey][] = $orderLineItemEntity;
                }
            }

            return $carry;
        }, []);
    }

    private function findProductNumberForTaxRate(OrderLineItemCollection $products, string $taxRate): string
    {
        foreach ($products as $product) {
            /** @var ?\Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice */
            $price = $product->getPrice();
            if (!is_null($price)) {
                /** @var ?\Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax */
                $calculatedTax = $price->getCalculatedTaxes()->first();
                if (!is_null($calculatedTax)) {
                    if ($calculatedTax->getTaxRate() === floatval($taxRate)) {
                        $product = $product->getProduct();
                        if (!is_null($product)) {
                            return $product->getProductNumber();
                        }
                    }
                }
            }
        }

        throw new \LogicException("No product with taxRate {$taxRate} found!");
    }
}
