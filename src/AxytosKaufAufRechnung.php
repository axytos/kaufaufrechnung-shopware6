<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware;

use Axytos\KaufAufRechnung\Shopware\Installer\PluginInstaller;
use Axytos\KaufAufRechnung\Shopware\Installer\PluginInstallerFactory;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}

class AxytosKaufAufRechnung extends Plugin
{
    private ErrorHandler $errorHandler;

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
        return PluginInstallerFactory::createInstaller(AxytosKaufAufRechnung::class, $this->container);
    }
}
