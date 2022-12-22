<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Installer;

use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\PaymentMethodEntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class PluginInstaller
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\PaymentMethodEntityRepository
     */
    private $paymentMethodRepository;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Installer\PluginIdProviderInterface
     */
    private $pluginIdProvider;

    public function __construct(
        PaymentMethodEntityRepository $paymentMethodRepository,
        PluginIdProviderInterface $pluginIdProvider
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->pluginIdProvider = $pluginIdProvider;
    }

    public function install(InstallContext $installContext): void
    {
        $this->addPaymentMethod($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->deactivatePaymentMethod($uninstallContext->getContext());
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->activatePaymentMethod($activateContext->getContext());
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->deactivatePaymentMethod($deactivateContext->getContext());
        ;
    }

    public function addPaymentMethod(Context $context): void
    {
        if ($this->paymentMethodRepository->containsByHandlerIdentifier(AxytosInvoicePaymentHandler::class, $context)) {
            return;
        }

        $pluginId = $this->pluginIdProvider->getPluginId($context);

        $this->paymentMethodRepository->create(
            AxytosInvoicePaymentHandler::class,
            AxytosInvoicePaymentHandler::NAME,
            AxytosInvoicePaymentHandler::DESCRIPTION,
            $pluginId,
            $context
        );
    }

    public function activatePaymentMethod(Context $context): void
    {
        $this->paymentMethodRepository->updateAllActiveStatesByHandlerIdentifer(AxytosInvoicePaymentHandler::class, true, $context);
    }

    public function deactivatePaymentMethod(Context $context): void
    {

        $this->paymentMethodRepository->updateAllActiveStatesByHandlerIdentifer(AxytosInvoicePaymentHandler::class, false, $context);
    }
}
