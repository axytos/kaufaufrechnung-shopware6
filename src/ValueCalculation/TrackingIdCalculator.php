<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class TrackingIdCalculator
{
    /**
     * @return array<string>
     */
    public function calculate(OrderEntity $orderEntity): array
    {
        $deliveries = $orderEntity->getDeliveries();

        if (!is_null($deliveries)) {
            $deliveryElements = $deliveries->getElements();

            if ($deliveryElements !== []) {
                reset($deliveryElements);
                /** @var OrderDeliveryEntity */
                $deliveryElement = $deliveryElements[key($deliveryElements)];

                return $deliveryElement->getTrackingCodes();
            }
        }

        return [];
    }
}
