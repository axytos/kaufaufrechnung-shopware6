<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\ValueCalculation;

use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductIdCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PromotionIdentifierCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\ProductEntity;

class PositionProductIdCalculatorTest extends TestCase
{
    /** @var PromotionIdentifierCalculator&MockObject */
    private $promotionIdentifierCalculator;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductIdCalculator
     */
    private $sut;

    public function setUp(): void
    {
        $this->promotionIdentifierCalculator = $this->createMock(PromotionIdentifierCalculator::class);

        $this->sut = new PositionProductIdCalculator($this->promotionIdentifierCalculator);
    }

    /**
     * @dataProvider dataProvider_test_calculate
     */
    public function test_calculate(
        string $orderLineItemType,
        string $productNumber,
        string $promotionIdentifier,
        string $expectedResult
    ): void {
        /** @var ProductEntity&MockObject */
        $product = $this->createMock(ProductEntity::class);
        $product->method('getProductNumber')->willReturn($productNumber);

        /** @var OrderLineItemEntity&MockObject */
        $orderLineItem = $this->createMock(OrderLineItemEntity::class);
        $orderLineItem->method('getType')->willReturn($orderLineItemType);
        $orderLineItem->method('getProduct')->willReturn($product);

        $this->promotionIdentifierCalculator
            ->method('calculate')
            ->with($orderLineItem)
            ->willReturn($promotionIdentifier);

        $actualResult = $this->sut->calculate($orderLineItem);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array<array<mixed>>
     */
    public function dataProvider_test_calculate(): array
    {
        return [
            [ LineItem::PRODUCT_LINE_ITEM_TYPE, 'ProductNumber', 'PromotionIdentifier', 'ProductNumber'],
            [ LineItem::PROMOTION_LINE_ITEM_TYPE, 'ProductNumber', 'PromotionIdentifier', 'PromotionIdentifier'],
        ];
    }

    public function test_calculate_throws_InvalidArgumentException_if_order_line_item_type_is_null(): void
    {
        /** @var OrderLineItemEntity&MockObject */
        $orderLineItem = $this->createMock(OrderLineItemEntity::class);
        $orderLineItem->method('getType')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);

        $this->sut->calculate($orderLineItem);
    }

    public function test_calculate_throws_InvalidArgumentException_if_order_line_item_type_is_not_supported(): void
    {
        /** @var OrderLineItemEntity&MockObject */
        $orderLineItem = $this->createMock(OrderLineItemEntity::class);
        $orderLineItem->method('getType')->willReturn('SomeNotSupportedType');

        $this->expectException(InvalidArgumentException::class);

        $this->sut->calculate($orderLineItem);
    }

    public function test_calculate_throws_InvalidArgumentException_if_product_order_line_item_has_no_product_associated(): void
    {
        /** @var OrderLineItemEntity&MockObject */
        $orderLineItem = $this->createMock(OrderLineItemEntity::class);
        $orderLineItem->method('getType')->willReturn(LineItem::PRODUCT_LINE_ITEM_TYPE);
        $orderLineItem->method('getProduct')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);

        $this->sut->calculate($orderLineItem);
    }
}
