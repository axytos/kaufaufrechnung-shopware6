<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\Invoice;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Invoice\TaxGroupInterface;

class TaxGroup implements TaxGroupInterface
{
    /**
     *
     * @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto
     */
    private $dto;

    public function __construct(CreateInvoiceTaxGroupDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @return float
     */
    public function getTaxPercent()
    {
        return floatval($this->dto->taxPercent);
    }

    /**
     * @return float
     */
    public function getValueToTax()
    {
        return floatval($this->dto->valueToTax);
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return floatval($this->dto->total);
    }
}
