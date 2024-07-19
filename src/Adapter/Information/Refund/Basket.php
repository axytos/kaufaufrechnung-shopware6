<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\Refund;

use Axytos\ECommerce\DataTransferObjects\RefundBasketDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Refund\BasketInterface;

class Basket implements BasketInterface
{
    /**
     *
     * @var \Axytos\ECommerce\DataTransferObjects\RefundBasketDto
     */
    private $dto;

    public function __construct(RefundBasketDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @return float
     */
    public function getNetTotal()
    {
        return floatval($this->dto->netTotal);
    }

    /**
     * @return float
     */
    public function getGrossTotal()
    {
        return floatval($this->dto->grossTotal);
    }


    /**
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Refund\BasketPositionInterface[]
     */
    public function getPositions()
    {
        $positions = is_null($this->dto->positions) ? [] : $this->dto->positions->getElements();
        return array_map(function ($position) {
            return new BasketPosition($position);
        }, $positions);
    }


    /**
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Refund\TaxGroupInterface[]
     */
    public function getTaxGroups()
    {
        $taxGroups = is_null($this->dto->taxGroups) ? [] : $this->dto->taxGroups->getElements();
        return array_map(function ($taxGroup) {
            return new TaxGroup($taxGroup);
        }, $taxGroups);
    }
}
