<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information;

use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\InvoiceInformationInterface;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\Invoice\Basket;

class InvoiceInformation implements InvoiceInformationInterface
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext
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

    public function getInvoiceNumber()
    {
        return $this->invoiceOrderContext->getOrderInvoiceNumber();
    }

    public function getBasket()
    {
        $basket = $this->invoiceOrderContext->getCreateInvoiceBasket();
        return new Basket($basket);
    }
}
