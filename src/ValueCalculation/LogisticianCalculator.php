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

        if (!is_null($deliveries)) {
            $deliveryElements = $deliveries->getElements();

            if ($deliveryElements !== []) {
                reset($deliveryElements);
                /** @var OrderDeliveryEntity */
                $deliveryElement = $deliveryElements[key($deliveryElements)];

                $shippingMethod = $deliveryElement->getShippingMethod();

                if (!is_null($shippingMethod)) {
                    $shippingMethodName = $shippingMethod->getName();
                    if (!is_null($shippingMethodName)) {
                        return $shippingMethodName;
                    }
                }
            }
        }

        return "";
    }
}
