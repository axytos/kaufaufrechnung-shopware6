<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware;

use Axytos\KaufAufRechnung\Shopware\Installer\PluginInstaller;
use Axytos\KaufAufRechnung\Shopware\Installer\PluginInstallerFactory;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}

/**
 * @phpstan-property \Symfony\Component\DependencyInjection\ContainerInterface $container
 */
class AxytosKaufAufRechnung extends Plugin
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler
     */
    private $errorHandler;

    public function setErrorHandler(ErrorHandler $errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }

    public function install(InstallContext $installContext): void
    {
        try {
            $installer = $this->createPluginInstaller();
            $installer->install($installContext);
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        try {
            $installer = $this->createPluginInstaller();
            $installer->uninstall($uninstallContext);
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);
        }
    }

    public function activate(ActivateContext $activateContext): void
    {
        try {
            $installer = $this->createPluginInstaller();
            $installer->activate($activateContext);
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);
        }
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        try {
            $installer = $this->createPluginInstaller();
            $installer->deactivate($deactivateContext);
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);
        }
    }

    private function createPluginInstaller(): PluginInstaller
    {
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = $this->container;
        return PluginInstallerFactory::createInstaller(AxytosKaufAufRechnung::class, $container);
    }
}
