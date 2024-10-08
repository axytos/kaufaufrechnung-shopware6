<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit;

use Axytos\ECommerce\Clients\Checkout\CheckoutClientInterface;
use Axytos\ECommerce\Clients\Checkout\CreditCheckAgreementLoadFailedException;
use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodCollectionFilter;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodPredicates;
use Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage\CheckoutConfirmPageExtension;
use Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage\CheckoutConfirmPageLoadedEventHandler;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;

/**
 * @internal
 */
class CheckoutConfirmPageLoadedEventHandlerTest extends TestCase
{
    private const EXTENSION_NAME = 'axytos_kauf_auf_rechnung_checkout_confirm_page';
    private const PAYMENT_METHOD_ID = 'paymentMethodId';

    /** @var CheckoutClientInterface&MockObject */
    private $checkoutClient;

    /** @var PaymentMethodCollectionFilter&MockObject */
    private $paymentMethodCollectionFilter;

    /** @var PaymentMethodPredicates&MockObject */
    private $paymentMethodPredicates;

    /**
     * @var CheckoutConfirmPageLoadedEventHandler
     */
    private $sut;

    /** @var CheckoutConfirmPage&MockObject */
    private $page;

    /** @var SalesChannelContext&MockObject */
    private $salesChannelContext;

    /** @var PaymentMethodEntity&MockObject */
    private $paymentMethod;

    /** @var PaymentMethodCollection&MockObject */
    private $paymentMethods;

    /** @var PaymentMethodCollection&MockObject */
    private $fallbackPaymentMethods;

    /** @var CheckoutConfirmPageLoadedEvent&MockObject */
    private $event;

    public function setUp(): void
    {
        $this->checkoutClient = $this->createMock(CheckoutClientInterface::class);
        $this->paymentMethodCollectionFilter = $this->createMock(PaymentMethodCollectionFilter::class);
        $this->paymentMethodPredicates = $this->createMock(PaymentMethodPredicates::class);

        $this->sut = new CheckoutConfirmPageLoadedEventHandler(
            $this->checkoutClient,
            $this->paymentMethodCollectionFilter,
            $this->paymentMethodPredicates
        );

        $this->page = $this->createMock(CheckoutConfirmPage::class);
        $this->salesChannelContext = $this->createMock(SalesChannelContext::class);
        $this->paymentMethod = $this->createMock(PaymentMethodEntity::class);
        $this->paymentMethods = $this->createMock(PaymentMethodCollection::class);
        $this->fallbackPaymentMethods = $this->createMock(PaymentMethodCollection::class);
        $this->event = $this->createMock(CheckoutConfirmPageLoadedEvent::class);

        $this->setUpEvent();
        $this->setUpPaymentMethodCollectionFilter();
    }

    private function setUpEvent(): void
    {
        $this->paymentMethod->method('getId')->willReturn(self::PAYMENT_METHOD_ID);
        $this->salesChannelContext->method('getPaymentMethod')->willReturn($this->paymentMethod);
        $this->page->method('getPaymentMethods')->willReturn($this->paymentMethods);
        $this->event->method('getPage')->willReturn($this->page);
        $this->event->method('getSalesChannelContext')->willReturn($this->salesChannelContext);
    }

    private function setUpPaymentMethodCollectionFilter(): void
    {
        $this->paymentMethodCollectionFilter
            ->method('filterPaymentMethodsNotUsingHandler')
            ->with($this->paymentMethods, AxytosInvoicePaymentHandler::class)
            ->willReturn($this->fallbackPaymentMethods)
        ;
    }

    private function setUpCheckout(
        bool $mustShowCreditCheckAgreement,
        string $getCreditCheckAgreementInfo
    ): void {
        $this->paymentMethodPredicates
            ->method('usesHandler')
            ->with($this->paymentMethod, AxytosInvoicePaymentHandler::class)
            ->willReturn($mustShowCreditCheckAgreement)
        ;

        $this->checkoutClient
            ->method('getCreditCheckAgreementInfo')
            ->willReturn($getCreditCheckAgreementInfo)
        ;
    }

    private function setUpCheckoutFailed(): void
    {
        $this->checkoutClient
            ->method('getCreditCheckAgreementInfo')
            ->willThrowException(new CreditCheckAgreementLoadFailedException(new \Exception()))
        ;
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function test_handle_adds_checkout_confirm_page_extension(): void
    {
        $matchExtension = $this->callback(function ($extension) {
            return $extension instanceof CheckoutConfirmPageExtension;
        });

        $this->page
            ->expects($this->once())
            ->method('addExtension')
            ->with(self::EXTENSION_NAME, $matchExtension)
        ;

        $this->sut->handle($this->event);
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function test_handle_sets_show_credit_check_agreement(): void
    {
        $this->setUpCheckout(true, 'CreditCheckAgreementInfo');

        $matchExtension = $this->callback(function (CheckoutConfirmPageExtension $extension) {
            return true === $extension->showCreditCheckAgreement;
        });

        $this->page
            ->expects($this->once())
            ->method('addExtension')
            ->with(self::EXTENSION_NAME, $matchExtension)
        ;

        $this->sut->handle($this->event);
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function test_handle_sets_credit_check_agreement_info(): void
    {
        $this->setUpCheckout(true, 'CreditCheckAgreementInfo');

        $matchExtension = $this->callback(function (CheckoutConfirmPageExtension $extension) {
            return 'CreditCheckAgreementInfo' === $extension->creditCheckAgreementInfo;
        });

        $this->page
            ->expects($this->once())
            ->method('addExtension')
            ->with(self::EXTENSION_NAME, $matchExtension)
        ;

        $this->sut->handle($this->event);
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function test_handle_does_not_show_credit_check_agreement_if_credit_check_agreement_cannot_be_loaded(): void
    {
        $this->setUpCheckoutFailed();

        $matchExtension = $this->callback(function (CheckoutConfirmPageExtension $extension) {
            return false === $extension->showCreditCheckAgreement
                && '' === $extension->creditCheckAgreementInfo;
        });

        $this->page
            ->expects($this->once())
            ->method('addExtension')
            ->with(self::EXTENSION_NAME, $matchExtension)
        ;

        $this->sut->handle($this->event);
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function test_handle_only_shows_fallback_payment_methods_if_credit_check_agreement_cannot_be_loaded(): void
    {
        $this->setUpCheckoutFailed();

        $this->page
            ->expects($this->once())
            ->method('setPaymentMethods')
            ->with($this->fallbackPaymentMethods)
        ;

        $this->sut->handle($this->event);
    }
}
