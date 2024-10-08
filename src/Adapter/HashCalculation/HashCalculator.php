<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\HashCalculation;

class HashCalculator
{
    /**
     * @var HashAlgorithmInterface
     */
    private $hashAlgorithm;

    public function __construct(HashAlgorithmInterface $hashAlgorithm)
    {
        $this->hashAlgorithm = $hashAlgorithm;
    }

    /**
     * @param \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\BasketUpdate\BasketInterface|\Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Checkout\BasketInterface $basket
     *
     * @return string
     */
    public function calculateBasketHash($basket)
    {
        $serializedBasket = $this->serializeBasket($basket);

        return $this->hashAlgorithm->compute($serializedBasket);
    }

    /**
     * @param \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\BasketUpdate\BasketInterface|\Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Checkout\BasketInterface $basket
     *
     * @return string
     */
    private function serializeBasket($basket)
    {
        $serializedBasket = json_encode($this->createBasketArray($basket));

        if (!is_string($serializedBasket)) {
            throw new \Exception(json_last_error_msg(), json_last_error());
        }

        return $serializedBasket;
    }

    /**
     * @param \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\BasketUpdate\BasketInterface|\Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Checkout\BasketInterface $basket
     *
     * @return mixed
     */
    private function createBasketArray($basket)
    {
        return [
            $basket->getNetTotal(),
            $basket->getGrossTotal(),
            $basket->getCurrency(),
            array_map([$this, 'createBasketPositionArray'], $basket->getPositions()),
        ];
    }

    /**
     * @param \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\BasketUpdate\BasketPositionInterface|\Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Checkout\BasketPositionInterface $basketPosition
     *
     * @return mixed
     */
    private function createBasketPositionArray($basketPosition)
    {
        return [
            $basketPosition->getProductNumber(),
            $basketPosition->getProductName(),
            $basketPosition->getProductCategory(),
            $basketPosition->getQuantity(),
            $basketPosition->getTaxPercent(),
            $basketPosition->getNetPricePerUnit(),
            $basketPosition->getGrossPricePerUnit(),
            $basketPosition->getNetPositionTotal(),
            $basketPosition->getGrossPositionTotal(),
        ];
    }
}
