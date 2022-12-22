<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\DataMapping;

use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDto;
use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class ReturnPositionModelDtoCollectionFactoryTest extends TestCase
{
    /** @var ReturnPositionModelDtoFactory&MockObject */
    private $returnPositionModelDtoFactory;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->returnPositionModelDtoFactory = $this->createMock(ReturnPositionModelDtoFactory::class);
        $this->sut = new ReturnPositionModelDtoCollectionFactory($this->returnPositionModelDtoFactory);
    }

    /**
     * @dataProvider dataProvider_test_create
     */
    public function test_create(OrderLineItemCollection $orderLineItemCollection): void
    {
        /** @var array<int, array<int, mixed>> */
        $mappings = $orderLineItemCollection->map(function (OrderLineItemEntity $orderLineItemEntity) {
            return [$orderLineItemEntity, $this->createMock(ReturnPositionModelDto::class)];
        });

        $this->returnPositionModelDtoFactory
            ->method('create')
            ->willReturnMap($mappings);

        $actual = $this->sut->create($orderLineItemCollection);

        // One extra for shipping
        $this->assertCount(count($orderLineItemCollection) + 1, $actual);

        foreach ($mappings as $mapping) {
            $this->assertContains($mapping[1], $actual);
        }
    }

    public function dataProvider_test_create(): array
    {
        return [
            [$this->createOrderLineItemCollection(0)],
            [$this->createOrderLineItemCollection(1)],
            [$this->createOrderLineItemCollection(2)],
            [$this->createOrderLineItemCollection(3)],
            [$this->createOrderLineItemCollection(4)],
            [$this->createOrderLineItemCollection(5)],
        ];
    }

    public function test_create_returns_empty_collection_for_null(): void
    {
        $actual = $this->sut->create(null);

        $this->assertInstanceOf(ReturnPositionModelDtoCollection::class, $actual);
        $this->assertCount(0, $actual);
    }

    private function createOrderLineItemCollection(int $count): OrderLineItemCollection
    {
        /** @var array */
        $elements = array_fill(0, $count, null);
        $elements = array_map([$this,'createOrderLineItem'], $elements);
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
}
