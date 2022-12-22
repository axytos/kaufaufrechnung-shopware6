<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDto;

class RefundBasketPositionDtoFactory
{
    public function create(string $productId, float $grossRefundTotal, float $netRefundTotal): RefundBasketPositionDto
    {
        $refundBasketPosition = new RefundBasketPositionDto();
        $refundBasketPosition->productId = $productId;
        $refundBasketPosition->grossRefundTotal = $grossRefundTotal;
        $refundBasketPosition->netRefundTotal = $netRefundTotal;
        return $refundBasketPosition;
    }
}
