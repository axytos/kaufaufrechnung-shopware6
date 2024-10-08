<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\Refund;

use Axytos\ECommerce\DataTransferObjects\RefundBasketPositionDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Refund\BasketPositionInterface;

class BasketPosition implements BasketPositionInterface
{
    /**
     * @var RefundBasketPositionDto
     */
    private $dto;

    public function __construct(RefundBasketPositionDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @return string
     */
    public function getProductNumber()
    {
        return strval($this->dto->productId);
    }

    /**
     * @return float
     */
    public function getNetRefundTotal()
    {
        return floatval($this->dto->netRefundTotal);
    }

    /**
     * @return float
     */
    public function getGrossRefundTotal()
    {
        return floatval($this->dto->grossRefundTotal);
    }
}
