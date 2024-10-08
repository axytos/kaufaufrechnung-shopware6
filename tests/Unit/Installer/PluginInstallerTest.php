<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit;

use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\PaymentMethodEntityRepository;
use Axytos\KaufAufRechnung\Shopware\Installer\PluginIdProviderInterface;
use Axytos\KaufAufRechnung\Shopware\Installer\PluginInstaller;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

/**
 * @internal
 */
class PluginInstallerTest extends TestCase
{
    const PLUGIN_ID = 'PLUGIN_ID';

    /** @var PaymentMethodEntityRepository&MockObject */
    private $paymentMethodRepository;

    /** @var PluginIdProviderInterface&MockObject */
    private $pluginIdProvider;

    /**
     * @var PluginInstaller
     */
    private $sut;

    /** @var Context&MockObject */
    private $context;

    /** @var InstallContext&MockObject */
    private $installContext;

    /** @var UninstallContext&MockObject */
    private $unintallContext;

    /** @var ActivateContext&MockObject */
    private $activateContext;

    /** @var DeactivateContext&MockObject */
    private $deactivateContext;

    public function setUp(): void
    {
        $this->paymentMethodRepository = $this->createMock(PaymentMethodEntityRepository::class);
        $this->pluginIdProvider = $this->createMock(PluginIdProviderInterface::class);

        $this->sut = new PluginInstaller($this->paymentMethodRepository, $this->pluginIdProvider);

        $this->context = $this->createMock(Context::class);
        $this->installContext = $this->createMock(InstallContext::class);
        $this->unintallContext = $this->createMock(UninstallContext::class);
        $this->activateContext = $this->createMock(ActivateContext::class);
        $this->deactivateContext = $this->createMock(DeactivateContext::class);

        $this->setUpPluginIdProvider();
        $this->setUpContexts();
    }

    private function setUpPluginIdProvider(): void
    {
        $this->pluginIdProvider
            ->method('getPluginId')
            ->with($this->context)
            ->willReturn(self::PLUGIN_ID)
        ;
    }

    private function setUpContexts(): void
    {
        $this->installContext->method('getContext')->willReturn($this->context);
        $this->unintallContext->method('getContext')->willReturn($this->context);
        $this->activateContext->method('getContext')->willReturn($this->context);
        $this->deactivateContext->method('getContext')->willReturn($this->context);
    }

    private function setUpPluginAlreadyInstalled(): void
    {
        $this->paymentMethodRepository
            ->method('containsByHandlerIdentifier')
            ->with(AxytosInvoicePaymentHandler::class, $this->context)
            ->willReturn(true)
        ;
    }

    private function setUpPluginNotInstalled(): void
    {
        $this->paymentMethodRepository
            ->method('containsByHandlerIdentifier')
            ->with(AxytosInvoicePaymentHandler::class, $this->context)
            ->willReturn(false)
        ;
    }

    public function test_install_plugin_already_installed_does_not_create_new_payment_method(): void
    {
        $this->setUpPluginAlreadyInstalled();

        $this->paymentMethodRepository
            ->expects($this->never())
            ->method('create')
            ->with(AxytosInvoicePaymentHandler::class, AxytosInvoicePaymentHandler::NAME, AxytosInvoicePaymentHandler::DESCRIPTION, self::PLUGIN_ID, $this->context)
        ;

        $this->sut->install($this->installContext);
    }

    public function test_install_plugin_not_installed_creates_new_payment_method(): void
    {
        $paymentName = 'Kauf auf Rechnung';
        $paymentDescription = 'Sie zahlen bequem die Rechnung, sobald Sie die Ware erhalten haben, innerhalb der Zahlfrist';
        $technicalName = 'payment_axytos_kaufaufrechnung';

        $this->setUpPluginNotInstalled();

        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('create')
            ->with(AxytosInvoicePaymentHandler::class, $paymentName, $paymentDescription, $technicalName, self::PLUGIN_ID, $this->context)
        ;

        $this->sut->install($this->installContext);
    }

    public function test_uninstall_deactivates_payment_method(): void
    {
        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('updateAllActiveStatesByHandlerIdentifer')
            ->with(AxytosInvoicePaymentHandler::class, false, $this->context)
        ;

        $this->sut->uninstall($this->unintallContext);
    }

    public function test_activate_activates_payment_method(): void
    {
        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('updateAllActiveStatesByHandlerIdentifer')
            ->with(AxytosInvoicePaymentHandler::class, true, $this->context)
        ;

        $this->sut->activate($this->activateContext);
    }

    public function test_deactivate_deactivates_payment_method(): void
    {
        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('updateAllActiveStatesByHandlerIdentifer')
            ->with(AxytosInvoicePaymentHandler::class, false, $this->context)
        ;

        $this->sut->deactivate($this->deactivateContext);
    }
}
