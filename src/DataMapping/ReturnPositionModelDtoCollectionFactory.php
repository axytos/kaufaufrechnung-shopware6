<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class ReturnPositionModelDtoCollectionFactory
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoFactory
     */
    private $returnPositionModelDtoFactory;

    public function __construct(ReturnPositionModelDtoFactory $returnPositionModelDtoFactory)
    {
        $this->returnPositionModelDtoFactory = $returnPositionModelDtoFactory;
    }

    public function create(?OrderLineItemCollection $orderLineItemCollection): ReturnPositionModelDtoCollection
    {
        if (is_null($orderLineItemCollection)) {
            return new ReturnPositionModelDtoCollection();
        }

        /** @var \Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDto[] */
        $positions = array_values($orderLineItemCollection->map(function (OrderLineItemEntity $orderLineItemEntity) {
            return $this->returnPositionModelDtoFactory->create($orderLineItemEntity);
        }));

        array_push($positions, $this->returnPositionModelDtoFactory->createShippingPosition());

        return new ReturnPositionModelDtoCollection(...$positions);
    }
}
