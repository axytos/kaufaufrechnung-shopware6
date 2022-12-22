<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;

class PositionTaxPercentCalculator
{
    public function calculate(?CalculatedPrice $calculatedPrice): float
    {
        if (is_null($calculatedPrice)) {
            return 0;
        }

        $calculatedTaxes = $calculatedPrice->getCalculatedTaxes();
        $taxRates = $calculatedTaxes->map(function (CalculatedTax $calculatedTax) {
            return $calculatedTax->getTaxRate();
        });

        return array_sum($taxRates);
    }
}
