<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDtoCollection;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPriceCalculator;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketPositionDtoFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\ProductEntity;

class RefundBasketPositionDtoCollectionFactoryTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketPositionDtoCollectionFactory
     */
    private $sut;

    /** @var RefundBasketPositionDtoFactory&MockObject */
    private $refundBasketPositionDtoFactory;

    /** @var PositionNetPriceCalculator&MockObject */
    private $positionNetPriceCalculator;

    /**
     * @var \Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection
     */
    private $orderLineItems;

    public function setUp(): void
    {
        $this->refundBasketPositionDtoFactory = $this->createMock(RefundBasketPositionDtoFactory::class);
        $this->positionNetPriceCalculator = $this->createMock(PositionNetPriceCalculator::class);
        $this->sut = new RefundBasketPositionDtoCollectionFactory($this->refundBasketPositionDtoFactory, $this->positionNetPriceCalculator);

        $this->setUpOrderLineItemCollection();
    }

    private function setUpOrderLineItemCollection(): void
    {
        $this->orderLineItems = new OrderLineItemCollection();

        $productNumber1 = 'productNumber1';
        $productNumber2 = 'productNumber2';
        $productNumber3 = 'productNumber3';
        $productNumber4 = 'productNumber4';

        $this->orderLineItems->add($this->createCreditOrderLineItem(7, -10.00, "credit 1"));
        $this->orderLineItems->add($this->createCreditOrderLineItem(19, -30.00, "credit 2"));
        $this->orderLineItems->add($this->createCreditOrderLineItem(19, -20.00, "credit 3"));

        $this->orderLineItems->add($this->createProductOrderLineItem(7, $productNumber1, "product 1"));
        $this->orderLineItems->add($this->createProductOrderLineItem(7, $productNumber2, "product 2"));
        $this->orderLineItems->add($this->createProductOrderLineItem(19, $productNumber3, "product 3"));
        $this->orderLineItems->add($this->createProductOrderLineItem(19, $productNumber4, "product 4"));



        $this->positionNetPriceCalculator
            ->method('calculate')->willReturnOnConsecutiveCalls(-9.35, -25.21, -16.81);

        $this->refundBasketPositionDtoFactory
            ->method('create')->willReturnOnConsecutiveCalls($this->createUpRefundBasketPositionDto($productNumber1, 10.00, 9.35), $this->createUpRefundBasketPositionDto($productNumber3, 50.00, 42.02));
    }

    private function createUpRefundBasketPositionDto(string $productNumber, float $grossRefundTotal, float $netRefundTotal): RefundBasketPositionDto
    {
        $refundBasketPositionDto = new RefundBasketPositionDto();
        $refundBasketPositionDto->grossRefundTotal = $grossRefundTotal;
        $refundBasketPositionDto->netRefundTotal = $netRefundTotal;
        $refundBasketPositionDto->productId = $productNumber;

        return $refundBasketPositionDto;
    }

    /**
     * @return OrderLineItemEntity&MockObject
     */
    private function createCreditOrderLineItem(float $taxRate, float $totalPrice, string $uniqueIdentifier)
    {
        /** @var OrderLineItemEntity&MockObject */
        $orderLineItemEntity = $this->createMock(OrderLineItemEntity::class);

        /** @var CalculatedPrice&MockObject */
        $price = $this->createMock(CalculatedPrice::class);

        /** @var CalculatedTaxCollection&MockObject */
        $calculatedTaxes = $this->createMock(CalculatedTaxCollection::class);

        /** @var CalculatedTax&MockObject */
        $calculatedTax = $this->createMock(CalculatedTax::class);

        $orderLineItemEntity
            ->method('getType')
            ->willReturn(LineItem::CREDIT_LINE_ITEM_TYPE);

        $orderLineItemEntity
            ->method('getPrice')
            ->willReturn($price);

        $orderLineItemEntity
            ->method('getUniqueIdentifier')
            ->willReturn($uniqueIdentifier);

        $price
            ->method('getCalculatedTaxes')
            ->willReturn($calculatedTaxes);

        $calculatedTaxes
            ->method('first')
            ->willReturn($calculatedTax);

        $calculatedTax
            ->method('getTaxRate')
            ->willReturn($taxRate);

        $price
            ->method('getTotalPrice')
            ->willReturn($totalPrice);

        return $orderLineItemEntity;
    }

    /**
     * @return OrderLineItemEntity&MockObject
     */
    private function createProductOrderLineItem(float $taxRate, string $productNumber, string $uniqueIdentifier)
    {
        /** @var OrderLineItemEntity&MockObject */
        $orderLineItemEntity = $this->createMock(OrderLineItemEntity::class);

        /** @var CalculatedPrice&MockObject */
        $price = $this->createMock(CalculatedPrice::class);

        /** @var CalculatedTaxCollection&MockObject */
        $calculatedTaxes = $this->createMock(CalculatedTaxCollection::class);

        /** @var CalculatedTax&MockObject */
        $calculatedTax = $this->createMock(CalculatedTax::class);

        /** @var ProductEntity&MockObject */
        $product = $this->createMock(ProductEntity::class);

        $orderLineItemEntity
            ->method('getType')
            ->willReturn(LineItem::PRODUCT_LINE_ITEM_TYPE);

        $orderLineItemEntity
            ->method('getPrice')
            ->willReturn($price);

        $price
            ->method('getCalculatedTaxes')
            ->willReturn($calculatedTaxes);

        $calculatedTaxes
            ->method('first')
            ->willReturn($calculatedTax);

        $calculatedTax
            ->method('getTaxRate')
            ->willReturn($taxRate);

        $orderLineItemEntity
            ->method('getProduct')
            ->willReturn($product);

        $orderLineItemEntity
            ->method('getUniqueIdentifier')
            ->willReturn($uniqueIdentifier);

        $product
            ->method('getProductNumber')
            ->willReturn($productNumber);

        return $orderLineItemEntity;
    }

    public function test_with_null_orderLineItems(): void
    {
        $expected = new RefundBasketPositionDtoCollection();
        $orderLineItems = null;

        $actual = $this->sut->create($orderLineItems);

        $this->assertEquals($expected, $actual);
    }

    public function test_creates_RefundBasketPositions_for_credit_taxRates(): void
    {
        $actual = $this->sut->create($this->orderLineItems);

        $this->assertEquals(2, $actual->count());
    }

    public function test_creates_maps_productNumber_for_RefundBasketPositions(): void
    {
        $actual = $this->sut->create($this->orderLineItems);

        $this->assertEquals("productNumber1", $actual->getElements()[0]->productId);
        $this->assertEquals("productNumber3", $actual->getElements()[1]->productId);
    }

    public function test_creates_maps_grossRefundTotals_for_RefundBasketPositions(): void
    {
        $actual = $this->sut->create($this->orderLineItems);

        $this->assertEquals(10, $actual->getElements()[0]->grossRefundTotal);
        $this->assertEquals(50, $actual->getElements()[1]->grossRefundTotal);
    }

    public function test_creates_maps_netRefundTotals_for_RefundBasketPositions(): void
    {
        $actual = $this->sut->create($this->orderLineItems);

        $this->assertEquals(9.35, $actual->getElements()[0]->netRefundTotal);
        $this->assertEquals(42.02, $actual->getElements()[1]->netRefundTotal);
    }
}
