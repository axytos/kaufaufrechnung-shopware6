<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\PaymentMethod;

use Axytos\ECommerce\Abstractions\FallbackModeConfigurationInterface;
use Axytos\ECommerce\Abstractions\FallbackModes;
use Axytos\ECommerce\Abstractions\PaymentMethodConfigurationInterface;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodPredicates;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\CashPayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DebitPayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\InvoicePayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class PaymentMethodPredicatesTest extends TestCase
{
    /** @var PaymentMethodConfigurationInterface&MockObject */
    private $paymentMethodConfiguration;

    /** @var FallbackModeConfigurationInterface&MockObject */
    private $fallbackModeConfiguration;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodPredicates
     */
    private $sut;

    public function setUp(): void
    {
        $this->paymentMethodConfiguration = $this->createMock(PaymentMethodConfigurationInterface::class);
        $this->fallbackModeConfiguration = $this->createMock(FallbackModeConfigurationInterface::class);

        $this->sut = new PaymentMethodPredicates(
            $this->paymentMethodConfiguration,
            $this->fallbackModeConfiguration
        );
    }

    /**
     * @dataProvider isAllowedFallbackTestDataProvider
     */
    public function test_isAllowedFallback(
        string $fallbackMode,
        string $paymentMethodConfig,
        bool $expectedOutcome
    ): void {
        $paymentMethodId = 'paymentMethodId';
        $paymentMethodEntity = $this->createPaymentMethodEntity($paymentMethodId);

        $this->fallbackModeConfiguration
            ->method('getFallbackMode')
            ->willReturn($fallbackMode);

        $this->paymentMethodConfiguration
            ->method($paymentMethodConfig)
            ->with($paymentMethodId)
            ->willReturn(true);

        $result = $this->sut->isAllowedFallback($paymentMethodEntity);

        $this->assertEquals($expectedOutcome, $result);
    }

    public function isAllowedFallbackTestDataProvider(): array
    {
        // FallbackMode, PaymentMethodConfig, ExpectedOutcome
        return [
            [FallbackModes::ALL_PAYMENT_METHODS, 'isSafe', true],
            [FallbackModes::ALL_PAYMENT_METHODS, 'isUnsafe', true],
            [FallbackModes::ALL_PAYMENT_METHODS, 'isIgnored', true],
            [FallbackModes::ALL_PAYMENT_METHODS, 'isNotConfigured', true],
            [FallbackModes::NO_UNSAFE_PAYMENT_METHODS, 'isSafe', true],
            [FallbackModes::NO_UNSAFE_PAYMENT_METHODS, 'isUnsafe', false],
            [FallbackModes::NO_UNSAFE_PAYMENT_METHODS, 'isIgnored', true],
            [FallbackModes::NO_UNSAFE_PAYMENT_METHODS, 'isNotConfigured', true],
            [FallbackModes::IGNORED_AND_NOT_CONFIGURED_PAYMENT_METHODS, 'isSafe', false],
            [FallbackModes::IGNORED_AND_NOT_CONFIGURED_PAYMENT_METHODS, 'isUnsafe', false],
            [FallbackModes::IGNORED_AND_NOT_CONFIGURED_PAYMENT_METHODS, 'isIgnored', true],
            [FallbackModes::IGNORED_AND_NOT_CONFIGURED_PAYMENT_METHODS, 'isNotConfigured', true],
            ['UndefinedFallbackMode', 'isSafe', true],
            ['UndefinedFallbackMode', 'isUnsafe', true],
            ['UndefinedFallbackMode', 'isIgnored', true],
            ['UndefinedFallbackMode', 'isNotConfigured', true],
        ];
    }

    /**
     * @dataProvider dataProvider_test_usesHandler
     */
    public function test_usesHandler(string $handlerIdentifier, string $handlerClass, bool $expectedOutcome): void
    {
        /** @var PaymentMethodEntity&MockObject */
        $paymentMethodEntity = $this->createMock(PaymentMethodEntity::class);
        $paymentMethodEntity
            ->method('getHandlerIdentifier')
            ->willReturn($handlerIdentifier);

        $actual = $this->sut->usesHandler($paymentMethodEntity, $handlerClass);

        $this->assertEquals($expectedOutcome, $actual);
    }

    public function dataProvider_test_usesHandler(): array
    {
        return [
            [InvoicePayment::class, InvoicePayment::class, true],
            [InvoicePayment::class, DebitPayment::class, false],
            [InvoicePayment::class, CashPayment::class, false],
            [InvoicePayment::class, PrePayment::class, false],

            [DebitPayment::class, InvoicePayment::class, false],
            [DebitPayment::class, DebitPayment::class, true],
            [DebitPayment::class, CashPayment::class, false],
            [DebitPayment::class, PrePayment::class, false],

            [CashPayment::class, InvoicePayment::class, false],
            [CashPayment::class, DebitPayment::class, false],
            [CashPayment::class, CashPayment::class, true],
            [CashPayment::class, PrePayment::class, false],

            [PrePayment::class, InvoicePayment::class, false],
            [PrePayment::class, DebitPayment::class, false],
            [PrePayment::class, CashPayment::class, false],
            [PrePayment::class, PrePayment::class, true],
        ];
    }

    private function createPaymentMethodEntity(string $paymentMethodId): PaymentMethodEntity
    {
        /** @var PaymentMethodEntity&MockObject */
        $paymentMethodEntity = $this->createMock(PaymentMethodEntity::class);
        $paymentMethodEntity
            ->method('getId')
            ->willReturn($paymentMethodId);
        $paymentMethodEntity
            ->method('getUniqueIdentifier')
            ->willReturn($paymentMethodId);

        return $paymentMethodEntity;
    }
}
