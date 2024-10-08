<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\PaymentMethod;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class PaymentMethodCollectionFilter
{
    /**
     * @var PaymentMethodPredicates
     */
    private $paymentMethodPredicates;

    public function __construct(PaymentMethodPredicates $paymentMethodPredicates)
    {
        $this->paymentMethodPredicates = $paymentMethodPredicates;
    }

    public function filterAllowedFallbackPaymentMethods(PaymentMethodCollection $paymentMethodCollection): PaymentMethodCollection
    {
        return $paymentMethodCollection->filter(function (PaymentMethodEntity $paymentMethodEntity) {
            return $this->paymentMethodPredicates->isAllowedFallback($paymentMethodEntity);
        });
    }

    public function filterNotUnsafePaymentMethods(PaymentMethodCollection $paymentMethodCollection): PaymentMethodCollection
    {
        return $paymentMethodCollection->filter(function (PaymentMethodEntity $paymentMethodEntity) {
            return $this->paymentMethodPredicates->isNotUnsafe($paymentMethodEntity);
        });
    }

    public function filterPaymentMethodsNotUsingHandler(PaymentMethodCollection $paymentMethodCollection, string $handlerClass): PaymentMethodCollection
    {
        return $paymentMethodCollection->filter(function (PaymentMethodEntity $paymentMethodEntity) use ($handlerClass) {
            return !$this->paymentMethodPredicates->usesHandler($paymentMethodEntity, $handlerClass);
        });
    }
}
