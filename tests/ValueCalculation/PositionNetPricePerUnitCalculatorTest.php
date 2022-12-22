<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\ValueCalculation;

use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPricePerUnitCalculator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;

class PositionNetPricePerUnitCalculatorTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPricePerUnitCalculator
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new PositionNetPricePerUnitCalculator();
    }

    public function test_calculate_returns_zero_if_calculated_price_is_null(): void
    {
        $this->assertEquals(0, $this->sut->calculate(null));
    }

    /**
     * @dataProvider dataProvider_test_calculate_returns_net_price_per_unit
     */
    public function test_calculate_returns_net_price_per_unit(float $tunitPrice, int $quantity, float $taxAmount, float $expectedNetPricePerUnit): void
    {
        /** @var CalculatedTaxCollection&MockObject */
        $calculatedTaxes = $this->createMock(CalculatedTaxCollection::class);
        $calculatedTaxes->method('getAmount')->willReturn($taxAmount);

        /** @var CalculatedPrice&MockObject */
        $calculatedPrice = $this->createMock(CalculatedPrice::class);
        $calculatedPrice->method('getUnitPrice')->willReturn($tunitPrice);
        $calculatedPrice->method('getQuantity')->willReturn($quantity);
        $calculatedPrice->method('getCalculatedTaxes')->willReturn($calculatedTaxes);

        $actual = $this->sut->calculate($calculatedPrice);

        $this->assertEquals($expectedNetPricePerUnit, $actual);
    }

    public function dataProvider_test_calculate_returns_net_price_per_unit(): array
    {
        return [
            [0, 0, 0, 0],
            [1, 0, 0, 1],
            [0, 1, 0, 0],
            [0, 0, 1, 0],

            [1, 1, 1, 0],
            [1, 2, 1, 0.5],
            [1, 2, 2, 0],
            [1, 3, 2, 0.33],

            [2, 1, 1, 1],
            [2, 2, 1, 1.5],
            [2, 3, 1, 1.67],

            [2, 1, 1 * 1, 1],
            [2, 2, 2 * 1, 1],
            [2, 3, 3 * 1, 1],

            [19.99, 0, 0 * 3.46, 19.99],
            [19.99, 1, 1 * 3.46, 16.53],
            [19.99, 2, 2 * 3.46, 16.53],
            [19.99, 3, 3 * 3.46, 16.53],
        ];
    }
}
