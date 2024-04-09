<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer;

use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class DocumentEntityRepository
{
    /**
     * @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository
     */
    private $documentRepository;

    public function __construct(EntityRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    public function findDocument(string $documentId, Context $context): DocumentEntity
    {
        $criteria = new Criteria([$documentId]);
        $criteria->addAssociation('documentType');
        $criteria->addAssociation('order');

        return $this->findFirst($criteria, $context);
    }

    private function findFirst(Criteria $criteria, Context $context): DocumentEntity
    {
        /** @var EntityFinder<DocumentEntity> */
        $entityFinder = new EntityFinder($this->documentRepository);
        return $entityFinder->findFirst($criteria, $context);
    }
}
