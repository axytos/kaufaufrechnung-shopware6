<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\PaymentMethod;

use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodCollectionFilter;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodPredicates;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;

class PaymentMethodCollectionFilterTest extends TestCase
{
    /** @var PaymentMethodPredicates&MockObject */
    private $paymentMethodPredicates;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodCollectionFilter
     */
    private $sut;

    public function setUp(): void
    {
        $this->paymentMethodPredicates = $this->createMock(PaymentMethodPredicates::class);

        $this->sut = new PaymentMethodCollectionFilter(
            $this->paymentMethodPredicates
        );
    }

    public function test_filterAllowedFallbackPaymentMethods_filters_allowed_payment_methods(): void
    {
        $paymentMethodCollection = $this->createPaymentMethodCollection(4);

        $config = [
            [$paymentMethodCollection->getAt(0), false],
            [$paymentMethodCollection->getAt(1), true],
            [$paymentMethodCollection->getAt(2), false],
            [$paymentMethodCollection->getAt(3), true],
        ];

        $this->paymentMethodPredicates
            ->method('isAllowedFallback')
            ->willReturnMap($config);

        $actual = $this->sut->filterAllowedFallbackPaymentMethods($paymentMethodCollection);

        $this->assertEquals(2, $actual->count());
        $this->assertNotContains($paymentMethodCollection->getAt(0), $actual);
        $this->assertContains($paymentMethodCollection->getAt(1), $actual);
        $this->assertNotContains($paymentMethodCollection->getAt(2), $actual);
        $this->assertContains($paymentMethodCollection->getAt(3), $actual);
    }

    public function test_filterNotUnsafePaymentMethods_filters_not_unsafe_payment_methods(): void
    {
        $paymentMethodCollection = $this->createPaymentMethodCollection(4);

        $config = [
            [$paymentMethodCollection->getAt(0), false],
            [$paymentMethodCollection->getAt(1), true],
            [$paymentMethodCollection->getAt(2), false],
            [$paymentMethodCollection->getAt(3), true],
        ];

        $this->paymentMethodPredicates
            ->method('isNotUnsafe')
            ->willReturnMap($config);

        $actual = $this->sut->filterNotUnsafePaymentMethods($paymentMethodCollection);

        $this->assertEquals(2, $actual->count());
        $this->assertNotContains($paymentMethodCollection->getAt(0), $actual);
        $this->assertContains($paymentMethodCollection->getAt(1), $actual);
        $this->assertNotContains($paymentMethodCollection->getAt(2), $actual);
        $this->assertContains($paymentMethodCollection->getAt(3), $actual);
    }

    public function test_filterPaymentMethodsNotUsingHandler(): void
    {
        $handlerClass = "handlerClass";
        $paymentMethodCollection = $this->createPaymentMethodCollection(4);

        $config = [
            [$paymentMethodCollection->getAt(0), $handlerClass, false],
            [$paymentMethodCollection->getAt(1), $handlerClass, true],
            [$paymentMethodCollection->getAt(2), $handlerClass, false],
            [$paymentMethodCollection->getAt(3), $handlerClass, false],
        ];

        $this->paymentMethodPredicates
            ->method('usesHandler')
            ->willReturnMap($config);

        $actual = $this->sut->filterPaymentMethodsNotUsingHandler($paymentMethodCollection, $handlerClass);

        $this->assertEquals(3, $actual->count());
        $this->assertContains($paymentMethodCollection->getAt(0), $actual);
        $this->assertNotContains($paymentMethodCollection->getAt(1), $actual);
        $this->assertContains($paymentMethodCollection->getAt(2), $actual);
        $this->assertContains($paymentMethodCollection->getAt(3), $actual);
    }

    private function createPaymentMethodCollection(int $count): PaymentMethodCollection
    {
        $paymentMethods = [];

        for ($i = 0; $i < $count; $i++) {
            $paymentMethods[$i] = new PaymentMethodEntity();
            $paymentMethods[$i]->setId("paymentMethod$i");
            $paymentMethods[$i]->setUniqueIdentifier("paymentMethod$i");
        }

        return new PaymentMethodCollection($paymentMethods);
    }
}
