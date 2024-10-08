<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\PaymentMethod;

use Axytos\ECommerce\Abstractions\FallbackModeConfigurationInterface;
use Axytos\ECommerce\Abstractions\FallbackModes;
use Axytos\ECommerce\Abstractions\PaymentMethodConfigurationInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class PaymentMethodPredicates
{
    /**
     * @var PaymentMethodConfigurationInterface
     */
    private $paymentMethodConfiguration;
    /**
     * @var FallbackModeConfigurationInterface
     */
    private $fallbackModeConfiguration;

    public function __construct(
        PaymentMethodConfigurationInterface $paymentMethodConfiguration,
        FallbackModeConfigurationInterface $fallbackModeConfiguration
    ) {
        $this->paymentMethodConfiguration = $paymentMethodConfiguration;
        $this->fallbackModeConfiguration = $fallbackModeConfiguration;
    }

    public function isAllowedFallback(PaymentMethodEntity $paymentMethodEntity): bool
    {
        $fallbackMode = $this->fallbackModeConfiguration->getFallbackMode();

        switch ($fallbackMode) {
            case FallbackModes::ALL_PAYMENT_METHODS:
                return true;
            case FallbackModes::NO_UNSAFE_PAYMENT_METHODS:
                return $this->isNotUnsafe($paymentMethodEntity);
            case FallbackModes::IGNORED_AND_NOT_CONFIGURED_PAYMENT_METHODS:
                return $this->isIgnoredOrNotConfigured($paymentMethodEntity);
            default:
                return true;
        }
    }

    public function isNotUnsafe(PaymentMethodEntity $paymentMethodEntity): bool
    {
        $paymentMethodId = $paymentMethodEntity->getId();

        return !$this->paymentMethodConfiguration->isUnsafe($paymentMethodId);
    }

    public function isIgnoredOrNotConfigured(PaymentMethodEntity $paymentMethodEntity): bool
    {
        $paymentMethodId = $paymentMethodEntity->getId();

        return $this->paymentMethodConfiguration->isIgnored($paymentMethodId)
            || $this->paymentMethodConfiguration->isNotConfigured($paymentMethodId);
    }

    public function usesHandler(PaymentMethodEntity $paymentMethodEntity, string $handlerClass): bool
    {
        $handlerIdentifier = $paymentMethodEntity->getHandlerIdentifier();

        return $handlerIdentifier === $handlerClass;
    }
}
