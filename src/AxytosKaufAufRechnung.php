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

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}

/**
 * @phpstan-property \Symfony\Component\DependencyInjection\ContainerInterface $container
 */
class AxytosKaufAufRechnung extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $installer = $this->createPluginInstaller();
        $installer->install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $installer = $this->createPluginInstaller();
        $installer->uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $installer = $this->createPluginInstaller();
        $installer->activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $installer = $this->createPluginInstaller();
        $installer->deactivate($deactivateContext);
    }

    private function createPluginInstaller(): PluginInstaller
    {
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = $this->container;
        return PluginInstallerFactory::createInstaller(AxytosKaufAufRechnung::class, $container);
    }
}
