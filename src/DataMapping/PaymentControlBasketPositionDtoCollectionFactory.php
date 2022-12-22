<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\PaymentControlBasketPositionDtoCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class PaymentControlBasketPositionDtoCollectionFactory
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\PaymentControlBasketPositionDtoFactory
     */
    private $basketPositionFactory;

    public function __construct(PaymentControlBasketPositionDtoFactory $basketPositionFactory)
    {
        $this->basketPositionFactory = $basketPositionFactory;
    }

    public function create(?OrderEntity $orderEntity): PaymentControlBasketPositionDtoCollection
    {
        if (is_null($orderEntity) || is_null($orderEntity->getLineItems()) || count($orderEntity->getLineItems()) == 0) {
            return new PaymentControlBasketPositionDtoCollection();
        }

        /** @var \Axytos\ECommerce\DataTransferObjects\PaymentControlBasketPositionDto[] */
        $positions = $orderEntity->getLineItems()->map(function (OrderLineItemEntity $orderLineItemEntity) {
            return $this->basketPositionFactory->create($orderLineItemEntity);
        });

        /** @var \Axytos\ECommerce\DataTransferObjects\PaymentControlBasketPositionDto[] */
        $positions = array_values($positions);
        array_push($positions, $this->basketPositionFactory->createShippingPosition($orderEntity));

        $result = new PaymentControlBasketPositionDtoCollection(...$positions);

        return $result;
    }
}
