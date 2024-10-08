<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionGrossPricePerUnitCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPriceCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPricePerUnitCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductIdCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductNameCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionTaxPercentCalculator;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class CreateInvoiceBasketPositionDtoFactory
{
    /**
     * @var PositionNetPriceCalculator
     */
    private $positionNetPriceCalculator;
    /**
     * @var PositionTaxPercentCalculator
     */
    private $positionTaxPercentCalculator;
    /**
     * @var PositionNetPricePerUnitCalculator
     */
    private $positionNetPricePerUnitCalculator;
    /**
     * @var PositionGrossPricePerUnitCalculator
     */
    private $positionGrossPricePerUnitCalculator;
    /**
     * @var PositionProductIdCalculator
     */
    private $positionProductIdCalculator;
    /**
     * @var PositionProductNameCalculator
     */
    private $positionProductNameCalculator;

    public function __construct(
        PositionNetPriceCalculator $positionNetPriceCalculator,
        PositionTaxPercentCalculator $positionTaxPercentCalculator,
        PositionNetPricePerUnitCalculator $positionNetPricePerUnitCalculator,
        PositionGrossPricePerUnitCalculator $positionGrossPricePerUnitCalculator,
        PositionProductIdCalculator $positionProductIdCalculator,
        PositionProductNameCalculator $positionProductNameCalculator
    ) {
        $this->positionNetPriceCalculator = $positionNetPriceCalculator;
        $this->positionTaxPercentCalculator = $positionTaxPercentCalculator;
        $this->positionNetPricePerUnitCalculator = $positionNetPricePerUnitCalculator;
        $this->positionGrossPricePerUnitCalculator = $positionGrossPricePerUnitCalculator;
        $this->positionProductIdCalculator = $positionProductIdCalculator;
        $this->positionProductNameCalculator = $positionProductNameCalculator;
    }

    public function create(OrderLineItemEntity $orderLineItemEntity): CreateInvoiceBasketPositionDto
    {
        $createInvoiceBasketPosition = new CreateInvoiceBasketPositionDto();
        $createInvoiceBasketPosition->grossPositionTotal = $orderLineItemEntity->getTotalPrice();
        $createInvoiceBasketPosition->quantity = $orderLineItemEntity->getQuantity();
        $createInvoiceBasketPosition->productId = $this->positionProductIdCalculator->calculate($orderLineItemEntity);
        $createInvoiceBasketPosition->productName = $this->positionProductNameCalculator->calculate($orderLineItemEntity);
        $createInvoiceBasketPosition->grossPricePerUnit = $this->positionGrossPricePerUnitCalculator->calculate($orderLineItemEntity->getPrice());
        $createInvoiceBasketPosition->netPositionTotal = $this->positionNetPriceCalculator->calculate($orderLineItemEntity->getPrice());
        $createInvoiceBasketPosition->netPricePerUnit = $this->positionNetPricePerUnitCalculator->calculate($orderLineItemEntity->getPrice());
        $createInvoiceBasketPosition->taxPercent = $this->positionTaxPercentCalculator->calculate($orderLineItemEntity->getPrice());

        return $createInvoiceBasketPosition;
    }

    public function createShippingPosition(OrderEntity $orderEntity): CreateInvoiceBasketPositionDto
    {
        $shippingPosition = new CreateInvoiceBasketPositionDto();
        $shippingPosition->productId = '0';
        $shippingPosition->productName = 'Shipping';
        $shippingPosition->quantity = 1;
        $shippingPosition->grossPositionTotal = $orderEntity->getShippingTotal();
        $shippingPosition->netPositionTotal = $this->positionNetPriceCalculator->calculate($orderEntity->getShippingCosts());
        $shippingPosition->taxPercent = $this->positionTaxPercentCalculator->calculate($orderEntity->getShippingCosts());
        $shippingPosition->netPricePerUnit = $this->positionNetPricePerUnitCalculator->calculate($orderEntity->getShippingCosts());
        $shippingPosition->grossPricePerUnit = $this->positionGrossPricePerUnitCalculator->calculate($orderEntity->getShippingCosts());

        return $shippingPosition;
    }
}
