<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\Refund;

use Axytos\ECommerce\DataTransferObjects\RefundBasketTaxGroupDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Refund\TaxGroupInterface;

class TaxGroup implements TaxGroupInterface
{
    /**
     * @var RefundBasketTaxGroupDto
     */
    private $dto;

    public function __construct(RefundBasketTaxGroupDto $dto)
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
