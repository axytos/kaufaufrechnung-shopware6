<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceTaxGroupDtoFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;

class CreateInvoiceTaxGroupDtoFactoryTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceTaxGroupDtoFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new CreateInvoiceTaxGroupDtoFactory();
    }

    public function test_maps_taxPercent(): void
    {
        $taxPercent = 10.1;

        /** @var CalculatedTax&MockObject */
        $calculatedTaxes = $this->createMock(CalculatedTax::class);
        $calculatedTaxes
            ->expects($this->once())
            ->method('getTaxRate')
            ->willReturn($taxPercent);

        $actual = $this->sut->create($calculatedTaxes)->taxPercent;

        $this->assertSame($taxPercent, $actual);
    }

    public function test_maps_valueToTax(): void
    {
        $valueToTax = 11.1;

        /** @var CalculatedTax&MockObject */
        $calculatedTaxes = $this->createMock(CalculatedTax::class);
        $calculatedTaxes
            ->expects($this->once())
            ->method('getPrice')
            ->willReturn($valueToTax);

        $actual = $this->sut->create($calculatedTaxes)->valueToTax;

        $this->assertSame($valueToTax, $actual);
    }

    public function test_maps_total(): void
    {
        $total = 12.1;

        /** @var CalculatedTax&MockObject */
        $calculatedTaxes = $this->createMock(CalculatedTax::class);
        $calculatedTaxes
            ->expects($this->once())
            ->method('getTax')
            ->willReturn($total);

        $actual = $this->sut->create($calculatedTaxes)->total;

        $this->assertSame($total, $actual);
    }
}
