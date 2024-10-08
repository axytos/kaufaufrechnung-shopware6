<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class PositionProductIdCalculator
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
                return 'shopware-credit-item-' . $orderLineItemEntity->getId();
            case LineItem::CUSTOM_LINE_ITEM_TYPE:
                return 'shopware-custom-item-' . $orderLineItemEntity->getId();
            case LineItem::DISCOUNT_LINE_ITEM:
                return 'shopware-discount-item-' . $orderLineItemEntity->getId();
            case LineItem::CONTAINER_LINE_ITEM:
                return 'shopware-container-item-' . $orderLineItemEntity->getId();
            default:
                $type = var_export($type, true);
                throw new \InvalidArgumentException("OrderLineItemEntity with type '{$type}' is not supported!");
        }
    }

    private function calculateForProduct(OrderLineItemEntity $orderLineItemEntity): string
    {
        $product = $orderLineItemEntity->getProduct();

        if (is_null($product)) {
            throw new \InvalidArgumentException('Product of OrderLineItemEntity must not be NULL!');
        }

        return $product->getProductNumber();
    }

    public function calculateForPromotion(OrderLineItemEntity $orderLineItemEntity): string
    {
        return $this->promotionIdentifierCalculator->calculate($orderLineItemEntity);
    }
}
