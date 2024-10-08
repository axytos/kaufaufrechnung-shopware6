<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\RefundInformationInterface;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\Refund\Basket;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;

class RefundInformation implements RefundInformationInterface
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

    public function getInvoiceNumber()
    {
        return $this->invoiceOrderContext->getOrderInvoiceNumber();
    }

    public function getBasket()
    {
        $dto = $this->invoiceOrderContext->getRefundBasket();

        return new Basket($dto);
    }
}
