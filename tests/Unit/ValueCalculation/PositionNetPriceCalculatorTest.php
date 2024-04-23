<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\ValueCalculation;

use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPriceCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;

class PositionNetPriceCalculatorTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionNetPriceCalculator
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new PositionNetPriceCalculator();
    }

    public function test_calculate_returns_zero_if_calculated_price_is_null(): void
    {
        $this->assertEquals(0, $this->sut->calculate(null));
    }

    /**
     * @dataProvider dataProvider_test_calculate_returns_net_price
     */
    #[DataProvider('dataProvider_test_calculate_returns_net_price')]
    public function test_calculate_returns_net_price(float $totalPrice, float $taxAmount, float $expectedNetPrice): void
    {
        /** @var CalculatedTaxCollection&MockObject */
        $calculatedTaxes = $this->createMock(CalculatedTaxCollection::class);
        $calculatedTaxes->method('getAmount')->willReturn($taxAmount);

        /** @var CalculatedPrice&MockObject */
        $calculatedPrice = $this->createMock(CalculatedPrice::class);
        $calculatedPrice->method('getTotalPrice')->willReturn($totalPrice);
        $calculatedPrice->method('getCalculatedTaxes')->willReturn($calculatedTaxes);

        $actual = $this->sut->calculate($calculatedPrice);

        $this->assertEquals($expectedNetPrice, $actual);
    }

    /**
     * @return array<array<float>>
     */
    public static function dataProvider_test_calculate_returns_net_price(): array
    {
        return [
            [0, 0, 0],
            [1, 0, 1],
            [0, 1, 0],
            [1, 1, 0],
            [2, 1, 1],
            [1, 2, 0],
            [19.99, 3.46, 16.53],
        ];
    }
}
