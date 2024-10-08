<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDtoCollection;
use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceTaxGroupDtoCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;

/**
 * @internal
 */
class CreateInvoiceBasketDtoFactoryTest extends TestCase
{
    /** @var CreateInvoiceBasketPositionDtoCollectionFactory&MockObject */
    private $createInvoiceBasketPositionDtoCollectionFactory;

    /** @var CreateInvoiceTaxGroupDtoCollectionFactory&MockObject */
    private $createInvoiceTaxGroupDtoCollectionFactory;

    /**
     * @var CreateInvoiceBasketDtoFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->createInvoiceBasketPositionDtoCollectionFactory = $this->createMock(CreateInvoiceBasketPositionDtoCollectionFactory::class);
        $this->createInvoiceTaxGroupDtoCollectionFactory = $this->createMock(CreateInvoiceTaxGroupDtoCollectionFactory::class);

        $this->sut = new CreateInvoiceBasketDtoFactory(
            $this->createInvoiceBasketPositionDtoCollectionFactory,
            $this->createInvoiceTaxGroupDtoCollectionFactory
        );
    }

    public function test_create_maps_amount_total_as_gross_total(): void
    {
        $orderEntity = $this->createOrderEntity();

        $actual = $this->sut->create($orderEntity);

        $this->assertEquals($orderEntity->getAmountTotal(), $actual->grossTotal);
    }

    public function test_create_maps_amount_net_as_net_total(): void
    {
        $orderEntity = $this->createOrderEntity();

        $actual = $this->sut->create($orderEntity);

        $this->assertEquals($orderEntity->getAmountNet(), $actual->netTotal);
    }

    public function test_create_maps_order_line_items_as_positions(): void
    {
        $orderEntity = $this->createOrderEntity();

        /** @var CreateInvoiceBasketPositionDtoCollection&MockObject */
        $basketPositions = $this->createMock(CreateInvoiceBasketPositionDtoCollection::class);

        $this->createInvoiceBasketPositionDtoCollectionFactory
            ->method('create')
            ->with($orderEntity)
            ->willReturn($basketPositions)
        ;

        $actual = $this->sut->create($orderEntity);

        $this->assertSame($basketPositions, $actual->positions);
    }

    public function test_maps_tax_groups(): void
    {
        $taxGroups = new CreateInvoiceTaxGroupDtoCollection();
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

        $this->createInvoiceTaxGroupDtoCollectionFactory
            ->expects($this->once())
            ->method('create')
            ->with($calculatedTaxtes)
            ->willReturn($taxGroups)
        ;

        $actual = $this->sut->create($orderEntity)->taxGroups;

        $this->assertSame($taxGroups, $actual);
    }

    private function createOrderEntity(): OrderEntity
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getAmountTotal')->willReturn(123.45);
        $orderEntity->method('getAmountNet')->willReturn(67.89);
        $orderEntity->method('getLineItems')->willReturn($this->createMock(OrderLineItemCollection::class));

        return $orderEntity;
    }
}
