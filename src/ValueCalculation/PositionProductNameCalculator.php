<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class PositionProductNameCalculator
{
    /**
     * @var PromotionIdentifierCalculator
     */
    private $promotionIdentifierCalculator;

    public function __construct(PromotionIdentifierCalculator $promotionIdentifierCalculator)
    {
        $this->promotionIdentifierCalculator = $promotionIdentifierCalculator;
    }

    public function calculate(OrderLineItemEntity $orderLineItemEntity): string
    {
        $type = $orderLineItemEntity->getType();
        switch ($type) {
            case LineItem::PRODUCT_LINE_ITEM_TYPE:
                return $this->calculateForProduct($orderLineItemEntity);
            case LineItem::PROMOTION_LINE_ITEM_TYPE:
                return $this->calculateForPromotion($orderLineItemEntity);
            case LineItem::CREDIT_LINE_ITEM_TYPE:
                return $orderLineItemEntity->getLabel();
            case LineItem::CUSTOM_LINE_ITEM_TYPE:
                return $orderLineItemEntity->getLabel();
            case LineItem::DISCOUNT_LINE_ITEM:
                return $orderLineItemEntity->getLabel();
            case LineItem::CONTAINER_LINE_ITEM:
                return $orderLineItemEntity->getLabel();
            default:
                $type = var_export($type, true);
                throw new \InvalidArgumentException("Order Line item with type '{$type}' is not supported!");
        }
    }

    private function calculateForProduct(OrderLineItemEntity $orderLineItemEntity): string
    {
        return $orderLineItemEntity->getLabel();
    }

    public function calculateForPromotion(OrderLineItemEntity $orderLineItemEntity): string
    {
        return $this->promotionIdentifierCalculator->calculate($orderLineItemEntity);
    }
}
