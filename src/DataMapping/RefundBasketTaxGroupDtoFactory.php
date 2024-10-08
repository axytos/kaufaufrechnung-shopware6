<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketTaxGroupDto;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;

class RefundBasketTaxGroupDtoFactory
{
    public function create(CalculatedTax $calculatedTax): RefundBasketTaxGroupDto
    {
        $refundBasketTaxGroup = new RefundBasketTaxGroupDto();
        $refundBasketTaxGroup->taxPercent = $calculatedTax->getTaxRate();
        $refundBasketTaxGroup->valueToTax = $calculatedTax->getPrice();
        $refundBasketTaxGroup->total = $calculatedTax->getTax();

        return $refundBasketTaxGroup;
    }
}
