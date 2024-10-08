<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * @phpstan-template TEntity of Entity
 * @phpstan-template TEntityCollection of EntityCollection<TEntity>
 */
class EntityFinder
{
    /**
     * @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<TEntityCollection>
     */
    private $entityRepository;

    /**
     * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<TEntityCollection> $entityRepository
     */
    public function __construct(EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    /**
     * @phpstan-return TEntity
     */
    public function findFirst(Criteria $criteria, Context $context): Entity
    {
        $criteria->setLimit(1);
        $entitySearchResult = $this->entityRepository->search($criteria, $context);

        if ($entitySearchResult->count() < 1) {
            throw new \LogicException('Given criteria did not find any entities!');
        }

        /** @var TEntity */
        return $entitySearchResult->first();
    }
}
