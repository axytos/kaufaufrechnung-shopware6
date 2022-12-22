<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketPositionDtoCollection;
use Axytos\KaufAufRechnung\Shopware\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\BasketPositionDtoCollectionFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Currency\CurrencyEntity;

class BasketDtoFactoryTest extends TestCase
{
    /** @var BasketPositionDtoCollectionFactory&MockObject */
    private $basketPositionDtoCollectionFactory;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\BasketDtoFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->basketPositionDtoCollectionFactory = $this->createMock(BasketPositionDtoCollectionFactory::class);

        $this->sut = new BasketDtoFactory(
            $this->basketPositionDtoCollectionFactory
        );
    }

    public function test_create_maps_currency_iso_code(): void
    {
        $orderEntity = $this->createOrderEntity();

        /** @var CurrencyEntity */
        $currency = $orderEntity->getCurrency();
        $currencyIsoCode = $currency->getIsoCode();

        $actual = $this->sut->create($orderEntity);

        $this->assertEquals($currencyIsoCode, $actual->currency);
    }

    public function test_create_maps_currency_as_null_if_currency_is_not_set(): void
    {
        $orderEntity = $this->createOrderEntity(false);

        $actual = $this->sut->create($orderEntity);

        $this->assertNull($actual->currency);
    }

    public function test_create_maps_amount_total_as_gross_total(): void
    {
        $orderEntity = $this->createOrderEntity();

        $actual = $this->sut->create($orderEntity);

        $this->assertEquals($orderEntity->getAmountTotal(), $actual->grossTotal);
    }

    public function test_create_maps_amount_net_as_net_total(): void
    {
        $orderEntity = $this->createOrderEntity();

        $actual = $this->sut->create($orderEntity);

        $this->assertEquals($orderEntity->getAmountNet(), $actual->netTotal);
    }

    public function test_create_maps_order_line_items_as_positions(): void
    {
        $orderEntity = $this->createOrderEntity(false);

        /** @var BasketPositionDtoCollection&MockObject */
        $basketPositions = $this->createMock(BasketPositionDtoCollection::class);

        $this->basketPositionDtoCollectionFactory
            ->method('create')
            ->with($orderEntity)
            ->willReturn($basketPositions);

        $acutal = $this->sut->create($orderEntity);

        $this->assertSame($basketPositions, $acutal->positions);
    }

    private function createOrderEntity(bool $hasCurrency = true): OrderEntity
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getAmountTotal')->willReturn(123.45);
        $orderEntity->method('getAmountNet')->willReturn(67.89);
        $orderEntity->method('getLineItems')->willReturn($this->createMock(OrderLineItemCollection::class));

        if ($hasCurrency) {
            /** @var CurrencyEntity&MockObject */
            $currency = $this->createMock(CurrencyEntity::class);
            $currency->method('getIsoCode')->willReturn('EUR');
            $orderEntity->method('getCurrency')->willReturn($currency);
        }

        return $orderEntity;
    }
}
