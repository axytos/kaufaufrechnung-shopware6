<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketDto;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Currency\CurrencyEntity;

class BasketDtoFactory
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\BasketPositionDtoCollectionFactory
     */
    private $basketPositionCollectionFactory;

    public function __construct(BasketPositionDtoCollectionFactory $basketPositionCollectionFactory)
    {
        $this->basketPositionCollectionFactory = $basketPositionCollectionFactory;
    }

    public function create(OrderEntity $orderEntity): BasketDto
    {
        $basket = new BasketDto();
        $basket->currency = $this->getCurrencyIsoCode($orderEntity->getCurrency());
        $basket->grossTotal = $orderEntity->getAmountTotal();
        $basket->netTotal = $orderEntity->getAmountNet();
        $basket->positions = $this->basketPositionCollectionFactory->create($orderEntity);

        return $basket;
    }

    private function getCurrencyIsoCode(?CurrencyEntity $currency): ?string
    {
        if (is_null($currency)) {
            return null;
        }

        return $currency->getIsoCode();
    }
}
