<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;

class PositionNetPriceCalculator
{
    public function calculate(?CalculatedPrice $calculatedPrice): float
    {
        if (is_null($calculatedPrice)) {
            return 0;
        }

        $totalPrice = $calculatedPrice->getTotalPrice();
        $calculatedTaxes = $calculatedPrice->getCalculatedTaxes();
        $totalTax = $calculatedTaxes->getAmount();
        $netPrice = round($totalPrice - $totalTax, 2);

        if ($netPrice < 0) {
            return 0;
        }

        return $netPrice;
    }
}
