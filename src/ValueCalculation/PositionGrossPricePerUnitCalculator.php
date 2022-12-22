<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\ValueCalculation;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;

class PositionGrossPricePerUnitCalculator
{
    public function calculate(?CalculatedPrice $calculatedPrice): float
    {
        if (is_null($calculatedPrice)) {
            return 0;
        }

        return $calculatedPrice->getUnitPrice();
    }
}
