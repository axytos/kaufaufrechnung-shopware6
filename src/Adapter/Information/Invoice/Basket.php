<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\Invoice;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Invoice\BasketInterface;

class Basket implements BasketInterface
{
    /**
     * @var CreateInvoiceBasketDto
     */
    private $dto;

    public function __construct(CreateInvoiceBasketDto $dto)
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
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Invoice\BasketPositionInterface[]
     */
    public function getPositions()
    {
        return array_map(function ($position) {
            return new BasketPosition($position);
        }, $this->dto->positions->getElements());
    }

    /**
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Invoice\TaxGroupInterface[]
     */
    public function getTaxGroups()
    {
        return array_map(function ($taxGroup) {
            return new TaxGroup($taxGroup);
        }, $this->dto->taxGroups->getElements());
    }
}
