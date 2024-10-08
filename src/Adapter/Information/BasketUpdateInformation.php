<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\BasketUpdateInformationInterface;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\BasketUpdate\Basket;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;

class BasketUpdateInformation implements BasketUpdateInformationInterface
{
    /**
     * @var InvoiceOrderContext
     */
    private $invoiceOrderContext;

    public function __construct(InvoiceOrderContext $invoiceOrderContext)
    {
        $this->invoiceOrderContext = $invoiceOrderContext;
    }

    public function getOrderNumber()
    {
        return $this->invoiceOrderContext->getOrderNumber();
    }

    public function getBasket()
    {
        $dto = $this->invoiceOrderContext->getBasket();

        return new Basket($dto);
    }
}
