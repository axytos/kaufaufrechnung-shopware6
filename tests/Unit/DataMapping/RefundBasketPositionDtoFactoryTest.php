<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketPositionDtoFactory;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class RefundBasketPositionDtoFactoryTest extends TestCase
{
    /**
     * @var RefundBasketPositionDtoFactory
     */
    private $sut;

    /**
     * @var string
     */
    private $productId = 'productId';
    /**
     * @var float
     */
    private $grossRefundTotal = 12.2;
    /**
     * @var float
     */
    private $netRefundTotal = 10.4;

    public function setUp(): void
    {
        $this->sut = new RefundBasketPositionDtoFactory();
    }

    public function test_maps_product_id(): void
    {
        $actual = $this->sut->create($this->productId, $this->grossRefundTotal, $this->netRefundTotal)->productId;

        $this->assertEquals($this->productId, $actual);
    }

    public function test_maps_calculates_gross_refund_total(): void
    {
        $actual = $this->sut->create($this->productId, $this->grossRefundTotal, $this->netRefundTotal)->grossRefundTotal;

        $this->assertSame($this->grossRefundTotal, $actual);
    }

    public function test_maps_calculates_net_refund_total(): void
    {
        $actual = $this->sut->create($this->productId, $this->grossRefundTotal, $this->netRefundTotal)->netRefundTotal;

        $this->assertSame($this->netRefundTotal, $actual);
    }
}
