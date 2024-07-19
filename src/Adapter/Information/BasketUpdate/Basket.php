<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\BasketUpdate;

use Axytos\ECommerce\DataTransferObjects\BasketDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\BasketUpdate\BasketInterface;

class Basket implements BasketInterface
{
    /**
     *
     * @var \Axytos\ECommerce\DataTransferObjects\BasketDto
     */
    private $dto;

    public function __construct(BasketDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return strval($this->dto->currency);
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
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\BasketUpdate\BasketPositionInterface[]
     */
    public function getPositions()
    {
        return array_map(function ($position) {
            return new BasketPosition($position);
        }, $this->dto->positions->getElements());
    }
}
