<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information;

use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\CancelInformationInterface;

class CancelInformation implements CancelInformationInterface
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
}
