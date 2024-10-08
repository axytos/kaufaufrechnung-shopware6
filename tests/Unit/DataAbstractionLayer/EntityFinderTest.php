<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataAbstractionLayer;

use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\EntityFinder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

/**
 * @internal
 */
class EntityFinderTest extends TestCase
{
    /** @var EntityRepository<EntityCollection<Entity>>&MockObject */
    private $entityRepository;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\EntityFinder<Entity,EntityCollection<Entity>>
     */
    private $sut;

    public function setUp(): void
    {
        $this->entityRepository = $this->createMock(EntityRepository::class);

        $this->sut = new EntityFinder($this->entityRepository);
    }

    public function test_find_first_returns_first_entity(): void
    {
        $entity = $this->createMock(Entity::class);
        $criteria = $this->createMock(Criteria::class);
        $context = $this->createMock(Context::class);

        /** @var EntityCollection<Entity>&MockObject */
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(1);
        $searchResult->method('first')->willReturn($entity);
        $this->entityRepository->method('search')->with($criteria, $context)->willReturn($searchResult);

        $actual = $this->sut->findFirst($criteria, $context);

        $this->assertSame($entity, $actual);
    }

    public function test_find_first_limits_search_results_to_one(): void
    {
        $entity = $this->createMock(Entity::class);
        /** @var Criteria&MockObject */
        $criteria = $this->createMock(Criteria::class);
        $context = $this->createMock(Context::class);

        /** @var EntityCollection<Entity>&MockObject */
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(1);
        $searchResult->method('first')->willReturn($entity);
        $this->entityRepository->method('search')->with($criteria, $context)->willReturn($searchResult);

        $criteria->expects($this->once())->method('setLimit')->with(1);

        $this->sut->findFirst($criteria, $context);
    }

    public function test_find_first_throws_logic_exception_when_no_entities_are_found(): void
    {
        $entity = $this->createMock(Entity::class);
        $criteria = $this->createMock(Criteria::class);
        $context = $this->createMock(Context::class);

        /** @var EntityCollection<Entity>&MockObject */
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(0);
        $searchResult->method('first')->willReturn($entity);
        $this->entityRepository->method('search')->with($criteria, $context)->willReturn($searchResult);

        $this->expectException(\LogicException::class);

        $this->sut->findFirst($criteria, $context);
    }
}
