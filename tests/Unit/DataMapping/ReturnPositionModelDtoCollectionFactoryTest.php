<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDto;
use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

/**
 * @internal
 */
class ReturnPositionModelDtoCollectionFactoryTest extends TestCase
{
    /** @var ReturnPositionModelDtoFactory&MockObject */
    private $returnPositionModelDtoFactory;

    /**
     * @var ReturnPositionModelDtoCollectionFactory
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
    #[DataProvider('dataProvider_test_create')]
    public function test_create(OrderLineItemCollection $orderLineItemCollection): void
    {
        /** @var array<int, array<int, mixed>> */
        $mappings = $orderLineItemCollection->map(function (OrderLineItemEntity $orderLineItemEntity) {
            return [$orderLineItemEntity, $this->createMock(ReturnPositionModelDto::class)];
        });

        $this->returnPositionModelDtoFactory
            ->method('create')
            ->willReturnMap($mappings)
        ;

        $actual = $this->sut->create($orderLineItemCollection);

        // One extra for shipping
        $this->assertCount(count($orderLineItemCollection) + 1, $actual);

        foreach ($mappings as $mapping) {
            $this->assertContains($mapping[1], $actual);
        }
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProvider_test_create(): array
    {
        return [
            [self::createOrderLineItemCollection(0)],
            [self::createOrderLineItemCollection(1)],
            [self::createOrderLineItemCollection(2)],
            [self::createOrderLineItemCollection(3)],
            [self::createOrderLineItemCollection(4)],
            [self::createOrderLineItemCollection(5)],
        ];
    }

    public function test_create_returns_empty_collection_for_null(): void
    {
        $actual = $this->sut->create(null);

        $this->assertInstanceOf(ReturnPositionModelDtoCollection::class, $actual);
        $this->assertCount(0, $actual);
    }

    private static function createOrderLineItemCollection(int $count): OrderLineItemCollection
    {
        /** @var OrderLineItemEntity[] */
        $elements = array_fill(0, $count, null);
        $elements = array_map([self::class, 'createOrderLineItem'], $elements);

        return new OrderLineItemCollection($elements);
    }

    private static function createOrderLineItem(): OrderLineItemEntity
    {
        $id = bin2hex(random_bytes(64));

        $entity = new OrderLineItemEntity();
        $entity->setUniqueIdentifier($id);

        return $entity;
    }
}
