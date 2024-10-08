<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto;
use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketPositionDtoFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

/**
 * @internal
 */
class CreateInvoiceBasketPositionDtoCollectionFactoryTest extends TestCase
{
    /** @var CreateInvoiceBasketPositionDtoFactory&MockObject */
    private $createInvoiceBasketPositionDtoFactory;

    /**
     * @var CreateInvoiceBasketPositionDtoCollectionFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->createInvoiceBasketPositionDtoFactory = $this->createMock(CreateInvoiceBasketPositionDtoFactory::class);

        $this->sut = new CreateInvoiceBasketPositionDtoCollectionFactory(
            $this->createInvoiceBasketPositionDtoFactory
        );
    }

    public function test_create_returns_empty_collection_for_null(): void
    {
        $actual = $this->sut->create(null);

        $this->assertCount(0, $actual);
    }

    public function test_create_returns_empty_collection_for_null_line_items(): void
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getLineItems')->willReturn(null);

        $actual = $this->sut->create($orderEntity);

        $this->assertCount(0, $actual);
    }

    public function test_create_returns_empty_collection_for_empty_line_item_collection(): void
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getLineItems')->willReturn($this->createOrderLineItemCollection(0));
        $orderEntity->method('getShippingCosts')->willReturn($this->createShippingCosts());

        $actual = $this->sut->create($orderEntity);

        $this->assertCount(0, $actual);
    }

    public function test_create_returns_collection_with_mapped_line_items_and_shipping(): void
    {
        $count = 3;
        $orderLineItems = $this->createOrderLineItemCollection($count);
        $basketPositionDtos = $this->createCreateInvoiceBasketPositionDtoCollection($count);
        $shippingCosts = $this->createShippingCosts();
        $shippingPositionDto = $this->createCreateInvoiceBasketPositionDto();

        $mapping = [];
        for ($i = 0; $i < $count; ++$i) {
            array_push($mapping, [$orderLineItems->getAt($i), $basketPositionDtos[$i]]);
        }

        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getLineItems')->willReturn($orderLineItems);
        $orderEntity->method('getShippingCosts')->willReturn($shippingCosts);

        $this->createInvoiceBasketPositionDtoFactory
            ->method('create')
            ->willReturnMap($mapping)
        ;

        $this->createInvoiceBasketPositionDtoFactory
            ->method('createShippingPosition')
            ->with($orderEntity)
            ->willReturn($shippingPositionDto)
        ;

        $actual = $this->sut->create($orderEntity);

        $this->assertCount(4, $actual);
        $this->assertContains($shippingPositionDto, $actual);

        foreach ($basketPositionDtos as $dto) {
            $this->assertContains($dto, $actual);
        }
    }

    private function createCreateInvoiceBasketPositionDtoCollection(int $count): CreateInvoiceBasketPositionDtoCollection
    {
        /** @var CreateInvoiceBasketPositionDto[] */
        $elements = array_fill(0, $count, null);
        $elements = array_map([$this, 'createCreateInvoiceBasketPositionDto'], $elements);

        return new CreateInvoiceBasketPositionDtoCollection(...$elements);
    }

    private function createCreateInvoiceBasketPositionDto(): CreateInvoiceBasketPositionDto
    {
        return $this->createMock(CreateInvoiceBasketPositionDto::class);
    }

    private function createOrderLineItemCollection(int $count): OrderLineItemCollection
    {
        /** @var OrderLineItemEntity[] */
        $elements = array_fill(0, $count, null);
        $elements = array_map([$this, 'createOrderLineItem'], $elements);

        return new OrderLineItemCollection($elements);
    }

    private function createOrderLineItem(): OrderLineItemEntity
    {
        $id = bin2hex(random_bytes(64));

        /** @var OrderLineItemEntity&MockObject */
        $entity = $this->createMock(OrderLineItemEntity::class);
        $entity->method('getUniqueIdentifier')->willReturn($id);

        return $entity;
    }

    private function createShippingCosts(): CalculatedPrice
    {
        /** @var CalculatedPrice&MockObject */
        $shippingCosts = $this->createMock(CalculatedPrice::class);
        /** @var CalculatedTax&MockObject */
        $calculatedTax = $this->createMock(CalculatedTax::class);

        $shippingCosts->method('getQuantity')->willReturn(1);
        $shippingCosts->method('getTotalPrice')->willReturn(100.0);
        $shippingCosts->method('getCalculatedTaxes')->willReturn(new CalculatedTaxCollection([$calculatedTax]));
        $calculatedTax->method('getTaxRate')->willReturn(19.0);

        return $shippingCosts;
    }
}
