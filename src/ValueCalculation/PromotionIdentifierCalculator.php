<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class PromotionIdentifierCalculator
{
    public function calculate(OrderLineItemEntity $orderLineItemEntity): string
    {
        $promotionName = $this->getPromotionName($orderLineItemEntity);
        $promotionCode = $this->getPromotionCode($orderLineItemEntity);

        return "$promotionName $promotionCode";
    }

    private function getPromotionName(OrderLineItemEntity $orderLineItemEntity): ?string
    {
        $promotion = $orderLineItemEntity->getPromotion();

        if (is_null($promotion)) {
            return null;
        }

        return $promotion->getName();
    }

    private function getPromotionCode(OrderLineItemEntity $orderLineItemEntity): ?string
    {
        return $orderLineItemEntity->getReferencedId();
    }
}
