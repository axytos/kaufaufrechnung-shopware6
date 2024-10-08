<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketPositionDtoCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class BasketPositionDtoCollectionFactory
{
    /**
     * @var BasketPositionDtoFactory
     */
    private $basketPositionFactory;

    public function __construct(BasketPositionDtoFactory $basketPositionFactory)
    {
        $this->basketPositionFactory = $basketPositionFactory;
    }

    public function create(?OrderEntity $orderEntity): BasketPositionDtoCollection
    {
        if (is_null($orderEntity) || is_null($orderEntity->getLineItems()) || 0 === count($orderEntity->getLineItems())) {
            return new BasketPositionDtoCollection();
        }

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketPositionDto[] */
        $positions = $orderEntity->getLineItems()->map(function (OrderLineItemEntity $orderLineItemEntity) {
            return $this->basketPositionFactory->create($orderLineItemEntity);
        });

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketPositionDto[] */
        $positions = array_values($positions);
        array_push($positions, $this->basketPositionFactory->createShippingPosition($orderEntity));

        return new BasketPositionDtoCollection(...$positions);
    }
}
