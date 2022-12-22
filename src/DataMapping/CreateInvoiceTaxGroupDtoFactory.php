<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;

class CreateInvoiceTaxGroupDtoFactory
{
    public function create(CalculatedTax $calculatedTax): CreateInvoiceTaxGroupDto
    {
        $createInvoiceTaxGroupDto = new CreateInvoiceTaxGroupDto();
        $createInvoiceTaxGroupDto->taxPercent = $calculatedTax->getTaxRate();
        $createInvoiceTaxGroupDto->valueToTax = $calculatedTax->getPrice();
        $createInvoiceTaxGroupDto->total = $calculatedTax->getTax();

        return $createInvoiceTaxGroupDto;
    }
}
