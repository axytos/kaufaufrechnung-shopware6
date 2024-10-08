<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\Checkout;

use Axytos\ECommerce\DataTransferObjects\BasketPositionDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Checkout\BasketPositionInterface;

class BasketPosition implements BasketPositionInterface
{
    /**
     * @var BasketPositionDto
     */
    private $dto;

    public function __construct(BasketPositionDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @return string|null
     */
    public function getProductCategory()
    {
        return $this->dto->productCategory;
    }

    /**
     * @return string
     */
    public function getProductNumber()
    {
        return strval($this->dto->productId);
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return strval($this->dto->productName);
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return floatval($this->dto->quantity);
    }

    /**
     * @return float
     */
    public function getTaxPercent()
    {
        return floatval($this->dto->taxPercent);
    }

    /**
     * @return float
     */
    public function getNetPricePerUnit()
    {
        return floatval($this->dto->netPricePerUnit);
    }

    /**
     * @return float
     */
    public function getGrossPricePerUnit()
    {
        return floatval($this->dto->grossPricePerUnit);
    }

    /**
     * @return float
     */
    public function getNetPositionTotal()
    {
        return floatval($this->dto->netPositionTotal);
    }

    /**
     * @return float
     */
    public function getGrossPositionTotal()
    {
        return floatval($this->dto->grossPositionTotal);
    }
}
