<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage;

use Axytos\ECommerce\Clients\Checkout\CheckoutClientInterface;
use Axytos\ECommerce\Clients\Checkout\CreditCheckAgreementLoadFailedException;
use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodCollectionFilter;
use Axytos\KaufAufRechnung\Shopware\PaymentMethod\PaymentMethodPredicates;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;

class CheckoutConfirmPageLoadedEventHandler
{
    private const EXTENSION_NAME = 'axytos_kauf_auf_rechnung_checkout_confirm_page';

    /**
     * @var CheckoutClientInterface
     */
    private $checkoutClient;
    /**
     * @var PaymentMethodCollectionFilter
     */
    private $paymentMethodCollectionFilter;
    /**
     * @var PaymentMethodPredicates
     */
    private $paymentMethodPredicates;

    public function __construct(
        CheckoutClientInterface $checkoutClient,
        PaymentMethodCollectionFilter $paymentMethodCollectionFilter,
        PaymentMethodPredicates $paymentMethodPredicates
    ) {
        $this->checkoutClient = $checkoutClient;
        $this->paymentMethodCollectionFilter = $paymentMethodCollectionFilter;
        $this->paymentMethodPredicates = $paymentMethodPredicates;
    }

    public function handle(CheckoutConfirmPageLoadedEvent $event): void
    {
        try {
            $this->showCreditCheckAgreement($event);
        } catch (CreditCheckAgreementLoadFailedException $e) {
            $this->showFallbackPaymentMethods($event);
        }
    }

    private function showCreditCheckAgreement(CheckoutConfirmPageLoadedEvent $event): void
    {
        /** @var CheckoutConfirmPage */
        $page = $event->getPage();

        $showCreditCheckAgreement = $this->getShowCreditCheckAgreement($event);
        $creditCheckAgreementInfo = $this->getCreditCheckAgreementInfo($event);

        $this->extendPage($page, $showCreditCheckAgreement, $creditCheckAgreementInfo);
    }

    private function showFallbackPaymentMethods(CheckoutConfirmPageLoadedEvent $event): void
    {
        /** @var CheckoutConfirmPage */
        $page = $event->getPage();

        $this->extendPage($page, false, '');
        $this->filterPaymentMethods($page);
    }

    private function filterPaymentMethods(CheckoutConfirmPage $page): void
    {
        $paymentMethods = $page->getPaymentMethods();
        $paymentMethods = $this->paymentMethodCollectionFilter->filterPaymentMethodsNotUsingHandler($paymentMethods, AxytosInvoicePaymentHandler::class);
        $page->setPaymentMethods($paymentMethods);
    }

    private function getShowCreditCheckAgreement(CheckoutConfirmPageLoadedEvent $event): bool
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $selectedPaymentMethod = $salesChannelContext->getPaymentMethod();

        return boolval($this->paymentMethodPredicates->usesHandler($selectedPaymentMethod, AxytosInvoicePaymentHandler::class));
    }

    private function getCreditCheckAgreementInfo(CheckoutConfirmPageLoadedEvent $event): string
    {
        return strval($this->checkoutClient->getCreditCheckAgreementInfo());
    }

    private function extendPage(
        CheckoutConfirmPage $page,
        bool $showCreditCheckAgreement,
        string $creditCheckAgreementInfo
    ): void {
        $extension = new CheckoutConfirmPageExtension();
        $extension->showCreditCheckAgreement = $showCreditCheckAgreement;
        $extension->creditCheckAgreementInfo = $creditCheckAgreementInfo;

        $page->addExtension(self::EXTENSION_NAME, $extension);
    }
}
