<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto;
use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceTaxGroupDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceTaxGroupDtoFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;

class CreateInvoiceTaxGroupDtoCollectionFactoryTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceTaxGroupDtoCollectionFactory
     */
    private $sut;

    /** @var CreateInvoiceTaxGroupDtoFactory&MockObject */
    private $createInvoiceTaxGroupDtoFactory;

    public function setUp(): void
    {
        $this->createInvoiceTaxGroupDtoFactory = $this->createMock(CreateInvoiceTaxGroupDtoFactory::class);
        $this->sut = new CreateInvoiceTaxGroupDtoCollectionFactory($this->createInvoiceTaxGroupDtoFactory);
    }

    public function test_with_null_orderLineItems(): void
    {
        $expected = new CreateInvoiceTaxGroupDtoCollection();
        $orderLineItems = null;

        $actual = $this->sut->create($orderLineItems);

        $this->assertEquals($expected, $actual);
    }

    public function test_with_orderLineItems(): void
    {
        $expected = new CreateInvoiceTaxGroupDtoCollection(new CreateInvoiceTaxGroupDto(), new CreateInvoiceTaxGroupDto());
        $calculatedTaxCollection = new CalculatedTaxCollection();
        for ($i = 0; $i < $expected->count(); $i++) {
            $orderLineItemEntity = new CalculatedTax($i, $i, $i);
            $calculatedTaxCollection->add($orderLineItemEntity);
        }

        $this->createInvoiceTaxGroupDtoFactory
            ->expects($this->exactly($expected->count()))
            ->method('create')
            ->withConsecutive(...$calculatedTaxCollection->map(function (CalculatedTax $orderLineItemEntity) {
                return [$orderLineItemEntity];
            }))
            ->willReturnOnConsecutiveCalls(...$expected->getElements());

        $actual = $this->sut->create($calculatedTaxCollection);

        $this->assertEquals($expected, $actual);
        $this->assertSame($expected->getElements(), $actual->getElements());
    }
}
