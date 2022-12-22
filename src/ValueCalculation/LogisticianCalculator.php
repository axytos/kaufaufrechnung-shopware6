<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class LogisticianCalculator
{
    public function calculate(OrderEntity $orderEntity): string
    {
        $deliveries = $orderEntity->getDeliveries();

        if ($deliveries) {
            $deliveryElements = $deliveries->getElements();

            if (is_array($deliveryElements) && !empty($deliveryElements)) {
                reset($deliveryElements);
                /** @var OrderDeliveryEntity */
                $deliveryElement = $deliveryElements[key($deliveryElements)];

                $shippingMethod = $deliveryElement->getShippingMethod();

                if ($shippingMethod) {
                    $shippingMethodName = $shippingMethod->getName();
                    if ($shippingMethodName) {
                        return $shippingMethodName;
                    }
                }
            }
        }

        return "";
    }
}
