<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;

class PositionNetPricePerUnitCalculator
{
    public function calculate(?CalculatedPrice $calculatedPrice): float
    {
        if (is_null($calculatedPrice)) {
            return 0;
        }

        $pricePerUnit = $calculatedPrice->getUnitPrice();

        $quantity = $calculatedPrice->getQuantity();
        $quantity = $quantity > 0 ? $quantity : 1;

        $taxAmount = $calculatedPrice->getCalculatedTaxes()->getAmount();

        $taxAmountPerUnit = round($taxAmount / $quantity, 2);
        $netPricePerUnit = round($pricePerUnit - $taxAmountPerUnit, 2);

        if ($netPricePerUnit < 0) {
            return 0;
        }

        return $netPricePerUnit;
    }
}
