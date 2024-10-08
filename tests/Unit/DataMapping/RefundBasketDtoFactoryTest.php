<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDtoCollection;
use Axytos\ECommerce\DataTransferObjects\RefundBasketTaxGroupDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketTaxGroupDtoCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;

/**
 * @internal
 */
class RefundBasketDtoFactoryTest extends TestCase
{
    /**
     * @var RefundBasketDtoFactory
     */
    private $sut;

    /** @var RefundBasketPositionDtoCollectionFactory&MockObject */
    private $refundBasketPositionDtoCollectionFactory;

    /** @var RefundBasketTaxGroupDtoCollectionFactory&MockObject */
    private $refundBasketTaxGroupDtoCollectionFactory;

    public function setUp(): void
    {
        $this->refundBasketPositionDtoCollectionFactory = $this->createMock(RefundBasketPositionDtoCollectionFactory::class);
        $this->refundBasketTaxGroupDtoCollectionFactory = $this->createMock(RefundBasketTaxGroupDtoCollectionFactory::class);
        $this->sut = new RefundBasketDtoFactory($this->refundBasketPositionDtoCollectionFactory, $this->refundBasketTaxGroupDtoCollectionFactory);
    }

    public function test_maps_net_total(): void
    {
        $amountNet = 10.1;

        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity
            ->expects($this->once())
            ->method('getAmountNet')
            ->willReturn($amountNet)
        ;

        $actual = $this->sut->create($orderEntity)->netTotal;

        $this->assertSame($amountNet, $actual);
    }

    public function test_maps_gross_total(): void
    {
        $grossTotal = 11.1;

        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity
            ->expects($this->once())
            ->method('getAmountTotal')
            ->willReturn($grossTotal)
        ;

        $actual = $this->sut->create($orderEntity)->grossTotal;

        $this->assertSame($grossTotal, $actual);
    }

    public function test_maps_positions(): void
    {
        $positions = new RefundBasketPositionDtoCollection();
        $lineItems = new OrderLineItemCollection();

        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems)
        ;

        $this->refundBasketPositionDtoCollectionFactory
            ->expects($this->once())
            ->method('create')
            ->with($lineItems)
            ->willReturn($positions)
        ;

        $actual = $this->sut->create($orderEntity)->positions;

        $this->assertSame($positions, $actual);
    }

    public function test_maps_tax_groups(): void
    {
        $taxGroups = new RefundBasketTaxGroupDtoCollection();
        $calculatedTaxtes = new CalculatedTaxCollection();

        /** @var CartPrice&MockObject */
        $cartPrice = $this->createMock(CartPrice::class);
        $cartPrice
            ->expects($this->once())
            ->method('getCalculatedTaxes')
            ->willReturn($calculatedTaxtes)
        ;

        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity
            ->expects($this->once())
            ->method('getPrice')
            ->willReturn($cartPrice)
        ;

        $this->refundBasketTaxGroupDtoCollectionFactory
            ->expects($this->once())
            ->method('create')
            ->with($calculatedTaxtes)
            ->willReturn($taxGroups)
        ;

        $actual = $this->sut->create($orderEntity)->taxGroups;

        $this->assertSame($taxGroups, $actual);
    }
}
