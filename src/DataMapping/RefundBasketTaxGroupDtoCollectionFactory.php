<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\RefundBasketTaxGroupDtoCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;

class RefundBasketTaxGroupDtoCollectionFactory
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketTaxGroupDtoFactory
     */
    private $refundBasketTaxGroupDtoFactory;

    public function __construct(RefundBasketTaxGroupDtoFactory $refundBasketTaxGroupDtoFactory)
    {
        $this->refundBasketTaxGroupDtoFactory = $refundBasketTaxGroupDtoFactory;
    }

    public function create(?CalculatedTaxCollection $calculatedTaxCollection = null): RefundBasketTaxGroupDtoCollection
    {
        if (is_null($calculatedTaxCollection)) {
            return new RefundBasketTaxGroupDtoCollection();
        }

        /** @var \Axytos\ECommerce\DataTransferObjects\RefundBasketTaxGroupDto[] */
        $positions = array_values($calculatedTaxCollection->map(function (CalculatedTax $calculatedTax) {
            return $this->refundBasketTaxGroupDtoFactory->create($calculatedTax);
        }));

        $result = new RefundBasketTaxGroupDtoCollection(...$positions);

        return $result;
    }
}
