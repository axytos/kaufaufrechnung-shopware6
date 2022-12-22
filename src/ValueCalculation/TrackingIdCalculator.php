<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class TrackingIdCalculator
{
    public function calculate(OrderEntity $orderEntity): array
    {
        $deliveries = $orderEntity->getDeliveries();

        if ($deliveries) {
            $deliveryElements = $deliveries->getElements();

            if (is_array($deliveryElements) && !empty($deliveryElements)) {
                reset($deliveryElements);
                /** @var OrderDeliveryEntity */
                $deliveryElement = $deliveryElements[key($deliveryElements)];

                return $deliveryElement->getTrackingCodes();
            }
        }

        return [];
    }
}
