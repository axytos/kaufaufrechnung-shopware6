<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDto;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductIdCalculator;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class ReturnPositionModelDtoFactory
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductIdCalculator
     */
    private $positionProductIdCalcualtor;

    public function __construct(PositionProductIdCalculator $positionProductIdCalcualtor)
    {
        $this->positionProductIdCalcualtor = $positionProductIdCalcualtor;
    }

    public function create(OrderLineItemEntity $orderLineItemEntity): ReturnPositionModelDto
    {
        $position = new ReturnPositionModelDto();
        $position->quantityToReturn = $orderLineItemEntity->getQuantity();
        $position->productId = $this->positionProductIdCalcualtor->calculate($orderLineItemEntity);
        return $position;
    }

    public function createShippingPosition(): ReturnPositionModelDto
    {
        $shippingPosition = new ReturnPositionModelDto();
        $shippingPosition->productId = '0';
        $shippingPosition->quantityToReturn = 1;

        return $shippingPosition;
    }
}
