<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\Shipping;

use Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Shipping\BasketPositionInterface;

class BasketPosition implements BasketPositionInterface
{
    /**
     *
     * @var \Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDto
     */
    private $dto;

    public function __construct(ShippingBasketPositionDto $dto)
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
     * @return int
     */
    public function getQuantity()
    {
        return intval($this->dto->quantity);
    }
}
