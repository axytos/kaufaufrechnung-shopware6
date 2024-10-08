<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\ValueCalculation;

use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionGrossPricePerUnitCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;

/**
 * @internal
 */
class PositionGrossPricePerUnitCalculatorTest extends TestCase
{
    /**
     * @var PositionGrossPricePerUnitCalculator
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new PositionGrossPricePerUnitCalculator();
    }

    public function test_calculate_returns_zero_if_calculated_price_is_null(): void
    {
        $this->assertEquals(0, $this->sut->calculate(null));
    }

    public function test_calculate_returns_price_per_unit(): void
    {
        $unitPrice = 123.45;

        /** @var CalculatedPrice&MockObject */
        $calculatedPrice = $this->createMock(CalculatedPrice::class);
        $calculatedPrice->method('getUnitPrice')->willReturn($unitPrice);

        $actual = $this->sut->calculate($calculatedPrice);

        $this->assertSame($unitPrice, $actual);
    }
}
