<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\ValueCalculation;

use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductNameCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PromotionIdentifierCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;

class PositionProductNameCalculatorTest extends TestCase
{
    /** @var PromotionIdentifierCalculator&MockObject */
    private $promotionIdentifierCalculator;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ValueCalculation\PositionProductNameCalculator
     */
    private $sut;

    public function setUp(): void
    {
        $this->promotionIdentifierCalculator = $this->createMock(PromotionIdentifierCalculator::class);

        $this->sut = new PositionProductNameCalculator($this->promotionIdentifierCalculator);
    }

    /**
     * @dataProvider dataProvider_test_calculate
     */
    public function test_calculate(
        string $orderLineItemType,
        string $orderLineItemLabel,
        string $promotionIdentifier,
        string $expectedResult
    ): void {
        /** @var OrderLineItemEntity&MockObject */
        $orderLineItem = $this->createMock(OrderLineItemEntity::class);
        $orderLineItem->method('getType')->willReturn($orderLineItemType);
        $orderLineItem->method('getLabel')->willReturn($orderLineItemLabel);

        $this->promotionIdentifierCalculator
            ->method('calculate')
            ->with($orderLineItem)
            ->willReturn($promotionIdentifier);

        $actualResult = $this->sut->calculate($orderLineItem);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProvider_test_calculate(): array
    {
        return [
            [ LineItem::PRODUCT_LINE_ITEM_TYPE, 'Label', 'PromotionIdentifier', 'Label'],
            [ LineItem::PROMOTION_LINE_ITEM_TYPE, 'Label', 'PromotionIdentifier', 'PromotionIdentifier'],
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
}
