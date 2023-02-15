<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketPositionDtoFactory;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPriceCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionTaxPercentCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPricePerUnitCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionGrossPricePerUnitCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductIdCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductNameCalculator;
use LogicException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;

class CreateInvoiceBasketPositionDtoFactoryTest extends TestCase
{
    /** @var PositionNetPriceCalculator&MockObject */
    private $positionNetPriceCalculator;
    /** @var PositionTaxPercentCalculator&MockObject */
    private $positionTaxPercentCalculator;
    /** @var PositionNetPricePerUnitCalculator&MockObject */
    private $positionNetPricePerUnitCalculator;
    /** @var PositionGrossPricePerUnitCalculator&MockObject */
    private $positionGrossPricePerUnitCalculator;
    /** @var PositionProductIdCalculator&MockObject */
    private $positionProductIdCalculator;
    /** @var PositionProductNameCalculator&MockObject */
    private $positionProductNameCalculator;


    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketPositionDtoFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->positionNetPriceCalculator = $this->createMock(PositionNetPriceCalculator::class);
        $this->positionTaxPercentCalculator = $this->createMock(PositionTaxPercentCalculator::class);
        $this->positionNetPricePerUnitCalculator = $this->createMock(PositionNetPricePerUnitCalculator::class);
        $this->positionGrossPricePerUnitCalculator = $this->createMock(PositionGrossPricePerUnitCalculator::class);
        $this->positionProductIdCalculator = $this->createMock(PositionProductIdCalculator::class);
        $this->positionProductNameCalculator = $this->createMock(PositionProductNameCalculator::class);

        $this->sut = new CreateInvoiceBasketPositionDtoFactory(
            $this->positionNetPriceCalculator,
            $this->positionTaxPercentCalculator,
            $this->positionNetPricePerUnitCalculator,
            $this->positionGrossPricePerUnitCalculator,
            $this->positionProductIdCalculator,
            $this->positionProductNameCalculator
        );
    }

    public function test_create_maps_total_price_as_gross_position_total(): void
    {
        $orderLineItem = $this->createOrderLineItemEntity();

        $actual = $this->sut->create($orderLineItem);

        $this->assertEquals($orderLineItem->getTotalPrice(), $actual->grossPositionTotal);
    }

    public function test_create_maps_calculates_product_id(): void
    {
        $orderLineItem = $this->createOrderLineItemEntity();

        $productId = "ProductId";
        $this->positionProductIdCalculator
            ->method('calculate')
            ->with($orderLineItem)
            ->willReturn($productId);

        $actual = $this->sut->create($orderLineItem);

        $this->assertEquals($productId, $actual->productId);
    }

    public function test_create_maps_calculates_product_name(): void
    {
        $orderLineItem = $this->createOrderLineItemEntity();

        $productName = "ProductName";
        $this->positionProductNameCalculator
            ->method('calculate')
            ->with($orderLineItem)
            ->willReturn($productName);

        $actual = $this->sut->create($orderLineItem);

        $this->assertEquals($productName, $actual->productName);
    }

    public function test_create_maps_quantity_as_quantity(): void
    {
        $orderLineItem = $this->createOrderLineItemEntity();

        $actual = $this->sut->create($orderLineItem);

        $this->assertEquals($orderLineItem->getQuantity(), $actual->quantity);
    }

    public function test_create_calculates_net_position_total(): void
    {
        $netPositionTotal = 50.78;
        $orderLineItem = $this->createOrderLineItemEntity();

        $this->positionNetPriceCalculator
            ->method('calculate')
            ->with($orderLineItem->getPrice())
            ->willReturn($netPositionTotal);

        $actual = $this->sut->create($orderLineItem);

        $this->assertEquals($netPositionTotal, $actual->netPositionTotal);
    }

    public function test_create_calculates_tax_percent(): void
    {
        $taxPercent = 51.78;
        $orderLineItem = $this->createOrderLineItemEntity();

        $this->positionTaxPercentCalculator
            ->method('calculate')
            ->with($orderLineItem->getPrice())
            ->willReturn($taxPercent);

        $actual = $this->sut->create($orderLineItem);

        $this->assertEquals($taxPercent, $actual->taxPercent);
    }

    public function test_create_calculates_net_price_per_unit(): void
    {
        $netPricePerUnit = 52.78;
        $orderLineItem = $this->createOrderLineItemEntity();

        $this->positionNetPricePerUnitCalculator
            ->method('calculate')
            ->with($orderLineItem->getPrice())
            ->willReturn($netPricePerUnit);

        $actual = $this->sut->create($orderLineItem);

        $this->assertEquals($netPricePerUnit, $actual->netPricePerUnit);
    }

    public function test_create_calculates_gross_price_per_unit(): void
    {
        $grossPricePerUnit = 53.78;
        $orderLineItem = $this->createOrderLineItemEntity();

        $this->positionGrossPricePerUnitCalculator
            ->method('calculate')
            ->with($orderLineItem->getPrice())
            ->willReturn($grossPricePerUnit);

        $actual = $this->sut->create($orderLineItem);

        $this->assertEquals($grossPricePerUnit, $actual->grossPricePerUnit);
    }

    //============================================================================================================

    public function test_createShippingPosition_sets_productId_to_zero(): void
    {
        $orderEntity = $this->createOrderEntity();

        $actual = $this->sut->createShippingPosition($orderEntity);

        $this->assertEquals('0', $actual->productId);
    }

    public function test_createShippingPosition_sets_productName_to_Shipping(): void
    {
        $orderEntity = $this->createOrderEntity();

        $actual = $this->sut->createShippingPosition($orderEntity);

        $this->assertEquals('Shipping', $actual->productName);
    }

    public function test_createShippingPosition_sets_quantity_to_one(): void
    {
        $orderEntity = $this->createOrderEntity();

        $actual = $this->sut->createShippingPosition($orderEntity);

        $this->assertEquals(1, $actual->quantity);
    }

    public function test_createShippingPosition_maps_order_shipping_total_as_gross_position_total(): void
    {
        $orderEntity = $this->createOrderEntity();

        $actual = $this->sut->createShippingPosition($orderEntity);

        $this->assertEquals($orderEntity->getShippingTotal(), $actual->grossPositionTotal);
    }

    public function test_createShippingPosition_calculates_net_position_total(): void
    {
        $orderEntity = $this->createOrderEntity();

        $netPrice = 56.78;
        $this->positionNetPriceCalculator
            ->method('calculate')
            ->with($orderEntity->getShippingCosts())
            ->willReturn($netPrice);

        $actual = $this->sut->createShippingPosition($orderEntity);

        $this->assertEquals($netPrice, $actual->netPositionTotal);
    }

    public function test_createShippingPosition_calculates_tax_percent(): void
    {
        $orderEntity = $this->createOrderEntity();

        $taxPercent = 56.78;
        $this->positionTaxPercentCalculator
            ->method('calculate')
            ->with($orderEntity->getShippingCosts())
            ->willReturn($taxPercent);

        $actual = $this->sut->createShippingPosition($orderEntity);

        $this->assertEquals($taxPercent, $actual->taxPercent);
    }

    public function test_createShippingPosition_calculates_net_price_per_unit(): void
    {
        $orderEntity = $this->createOrderEntity();

        $netPricePerUnit = 56.78;
        $this->positionNetPricePerUnitCalculator
            ->method('calculate')
            ->with($orderEntity->getShippingCosts())
            ->willReturn($netPricePerUnit);

        $actual = $this->sut->createShippingPosition($orderEntity);

        $this->assertEquals($netPricePerUnit, $actual->netPricePerUnit);
    }

    public function test_createShippingPosition_calculates_gross_price_per_unit(): void
    {
        $orderEntity = $this->createOrderEntity();

        $grossPricePerUnit = 56.78;
        $this->positionGrossPricePerUnitCalculator
            ->method('calculate')
            ->with($orderEntity->getShippingCosts())
            ->willReturn($grossPricePerUnit);

        $actual = $this->sut->createShippingPosition($orderEntity);

        $this->assertEquals($grossPricePerUnit, $actual->grossPricePerUnit);
    }

    //============================================================================================================

    public function createOrderEntity(): OrderEntity
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getShippingTotal')->willReturn(12.37);
        $orderEntity->method('getShippingCosts')->willReturn($this->createMock(CalculatedPrice::class));

        return $orderEntity;
    }

    public function createOrderLineItemEntity(): OrderLineItemEntity
    {
        /** @var OrderLineItemEntity&MockObject */
        $orderLineItem = $this->createMock(OrderLineItemEntity::class);
        $orderLineItem->method('getTotalPrice')->willReturn(12.34);
        $orderLineItem->method('getProduct')->willReturn($this->createMock(ProductEntity::class));
        $orderLineItem->method('getLabel')->willReturn('label');
        $orderLineItem->method('getQuantity')->willReturn(5);
        $orderLineItem->method('getPrice')->willReturn($this->createMock(CalculatedPrice::class));
        return $orderLineItem;
    }
}
