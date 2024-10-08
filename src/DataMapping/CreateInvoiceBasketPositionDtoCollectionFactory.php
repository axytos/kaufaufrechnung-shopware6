<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDtoCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class CreateInvoiceBasketPositionDtoCollectionFactory
{
    /**
     * @var CreateInvoiceBasketPositionDtoFactory
     */
    private $createInvoiceBasketPositionDtoFactory;

    public function __construct(CreateInvoiceBasketPositionDtoFactory $createInvoiceBasketPositionDtoFactory)
    {
        $this->createInvoiceBasketPositionDtoFactory = $createInvoiceBasketPositionDtoFactory;
    }

    public function create(?OrderEntity $orderEntity): CreateInvoiceBasketPositionDtoCollection
    {
        if (is_null($orderEntity) || is_null($orderEntity->getLineItems()) || 0 === count($orderEntity->getLineItems())) {
            return new CreateInvoiceBasketPositionDtoCollection();
        }

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto[] */
        $positions = $orderEntity->getLineItems()->map(function (OrderLineItemEntity $orderLineItemEntity) {
            return $this->createInvoiceBasketPositionDtoFactory->create($orderLineItemEntity);
        });

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto[] */
        $positions = array_values($positions);
        array_push($positions, $this->createInvoiceBasketPositionDtoFactory->createShippingPosition($orderEntity));

        return new CreateInvoiceBasketPositionDtoCollection(...$positions);
    }
}
