<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketPositionDtoCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class BasketPositionDtoCollectionFactory
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\BasketPositionDtoFactory
     */
    private $basketPositionFactory;

    public function __construct(BasketPositionDtoFactory $basketPositionFactory)
    {
        $this->basketPositionFactory = $basketPositionFactory;
    }

    public function create(?OrderEntity $orderEntity): BasketPositionDtoCollection
    {
        if (is_null($orderEntity) || is_null($orderEntity->getLineItems()) || count($orderEntity->getLineItems()) == 0) {
            return new BasketPositionDtoCollection();
        }

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketPositionDto[] */
        $positions = $orderEntity->getLineItems()->map(function (OrderLineItemEntity $orderLineItemEntity) {
            return $this->basketPositionFactory->create($orderLineItemEntity);
        });

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketPositionDto[] */
        $positions = array_values($positions);
        array_push($positions, $this->basketPositionFactory->createShippingPosition($orderEntity));

        $result = new BasketPositionDtoCollection(...$positions);

        return $result;
    }
}
