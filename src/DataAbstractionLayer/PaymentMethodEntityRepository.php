<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class PaymentMethodEntityRepository
{
    /**
     * @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<PaymentMethodCollection>
     */
    private $paymentMethodRepository;

    /**
     * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<PaymentMethodCollection> $paymentMethodRepository
     */
    public function __construct(EntityRepository $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function create(
        string $handlerIdentifier,
        string $name,
        string $description,
        string $pluginId,
        Context $context
    ): void {
        $paymentMethodData = [
            'handlerIdentifier' => $handlerIdentifier,
            'name' => $name,
            'description' => $description,
            'pluginId' => $pluginId,
        ];

        $this->paymentMethodRepository->create([$paymentMethodData], $context);
    }

    public function findAllByHandlerIdentifier(string $handlerIdentifier, Context $context): PaymentMethodCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $handlerIdentifier));

        return $this->findAll($criteria, $context);
    }

    public function containsByHandlerIdentifier(string $handlerIdentifier, Context $context): bool
    {
        $paymentMethods = $this->findAllByHandlerIdentifier($handlerIdentifier, $context);

        return $paymentMethods->count() > 0;
    }

    public function updateAllActiveStatesByHandlerIdentifer(
        string $handlerIdentifier,
        bool $isActive,
        Context $context
    ): void {
        $paymentMethods = $this->findAllByHandlerIdentifier($handlerIdentifier, $context);

        /** @var array<array<string, mixed>> */
        $data = array_values($paymentMethods->map(function (PaymentMethodEntity $entity) use ($isActive) {
            return [
                'id' => $entity->getId(),
                'active' => $isActive,
            ];
        }));

        $this->paymentMethodRepository->update($data, $context);
    }

    private function findAll(Criteria $criteria, Context $context): PaymentMethodCollection
    {
        $searchResult = $this->paymentMethodRepository->search($criteria, $context);

        /** @var iterable<PaymentMethodEntity> */
        $entities = $searchResult->getEntities();

        return new PaymentMethodCollection($entities);
    }
}
