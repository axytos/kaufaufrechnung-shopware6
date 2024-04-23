<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\ValueCalculation;

use Axytos\KaufAufRechnung\Shopware\ValueCalculation\PromotionIdentifierCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Promotion\PromotionEntity;

class PromotionIdentifierCalculatorTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ValueCalculation\PromotionIdentifierCalculator
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new PromotionIdentifierCalculator();
    }

    /**
     * @dataProvider dataProvider_test_calculate
     */
    #[DataProvider('dataProvider_test_calculate')]
    public function test_calculate(string $promotionName, string $promotionCode, string $expectedIdentifier): void
    {
        /** @var PromotionEntity&MockObject */
        $promotionEntity = $this->createMock(PromotionEntity::class);
        $promotionEntity->method('getName')->willReturn($promotionName);

        /** @var OrderLineItemEntity&MockObject */
        $orderLineItemEntity = $this->createMock(OrderLineItemEntity::class);
        $orderLineItemEntity->method('getPromotion')->willReturn($promotionEntity);
        $orderLineItemEntity->method('getReferencedId')->willReturn($promotionCode);

        $actualIdentifier = $this->sut->calculate($orderLineItemEntity);

        $this->assertEquals($expectedIdentifier, $actualIdentifier);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProvider_test_calculate(): array
    {
        return [
            ['', '', ' '],
            ['PromotionName', 'PromotionCode', 'PromotionName PromotionCode'],
        ];
    }
}
