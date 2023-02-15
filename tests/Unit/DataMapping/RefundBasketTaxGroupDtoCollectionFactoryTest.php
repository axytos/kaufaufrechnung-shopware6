<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketTaxGroupDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketTaxGroupDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketTaxGroupDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketTaxGroupDtoFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;

class RefundBasketTaxGroupDtoCollectionFactoryTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketTaxGroupDtoCollectionFactory
     */
    private $sut;

    /** @var RefundBasketTaxGroupDtoFactory&MockObject */
    private $refundBasketTaxGroupDtoFactory;

    public function setUp(): void
    {
        $this->refundBasketTaxGroupDtoFactory = $this->createMock(RefundBasketTaxGroupDtoFactory::class);
        $this->sut = new RefundBasketTaxGroupDtoCollectionFactory($this->refundBasketTaxGroupDtoFactory);
    }

    public function test_with_null_orderLineItems(): void
    {
        $expected = new RefundBasketTaxGroupDtoCollection();
        $orderLineItems = null;

        $actual = $this->sut->create($orderLineItems);

        $this->assertEquals($expected, $actual);
    }

    public function test_with_orderLineItems(): void
    {
        $expected = new RefundBasketTaxGroupDtoCollection(new RefundBasketTaxGroupDto(), new RefundBasketTaxGroupDto());
        $calculatedTaxCollection = new CalculatedTaxCollection();
        for ($i = 0; $i < $expected->count(); $i++) {
            $orderLineItemEntity = new CalculatedTax($i, $i, $i);
            $calculatedTaxCollection->add($orderLineItemEntity);
        }

        $this->refundBasketTaxGroupDtoFactory
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
