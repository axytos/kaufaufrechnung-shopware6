<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto;
use Shopware\Core\Checkout\Order\OrderEntity;

class CreateInvoiceBasketDtoFactory
{
    /**
     * @var CreateInvoiceBasketPositionDtoCollectionFactory
     */
    private $createInvoiceBasketPositionDtoCollectionFactory;
    /**
     * @var CreateInvoiceTaxGroupDtoCollectionFactory
     */
    private $createInvoiceTaxGroupDtoCollectionFactory;

    public function __construct(
        CreateInvoiceBasketPositionDtoCollectionFactory $createInvoiceBasketPositionDtoCollectionFactory,
        CreateInvoiceTaxGroupDtoCollectionFactory $createInvoiceTaxGroupDtoCollectionFactory
    ) {
        $this->createInvoiceBasketPositionDtoCollectionFactory = $createInvoiceBasketPositionDtoCollectionFactory;
        $this->createInvoiceTaxGroupDtoCollectionFactory = $createInvoiceTaxGroupDtoCollectionFactory;
    }

    public function create(OrderEntity $orderEntity): CreateInvoiceBasketDto
    {
        $createInvoiceBasket = new CreateInvoiceBasketDto();
        $createInvoiceBasket->grossTotal = $orderEntity->getAmountTotal();
        $createInvoiceBasket->netTotal = $orderEntity->getAmountNet();
        $createInvoiceBasket->positions = $this->createInvoiceBasketPositionDtoCollectionFactory->create($orderEntity);
        $createInvoiceBasket->taxGroups = $this->createInvoiceTaxGroupDtoCollectionFactory->create($orderEntity->getPrice()->getCalculatedTaxes());

        return $createInvoiceBasket;
    }
}
