<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests;

use Axytos\KaufAufRechnung\Shopware\AxytosKaufAufRechnung;
use Axytos\KaufAufRechnung\Shopware\Installer\PluginInstaller;
use Axytos\KaufAufRechnung\Shopware\Installer\PluginInstallerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginInstallerFactoryTest extends TestCase
{
    public function test_createInstaller_returns_instance_of_Plugininstaller(): void
    {
        /** @var ContainerInterface&MockObject */
        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturnCallback(function ($name) {
            switch ($name) {
                case 'payment_method.repository':
                    return $this->createMock(EntityRepositoryInterface::class);
                case PluginIdProvider::class:
                    return $this->createMock(PluginIdProvider::class);
                default:
                    return null;
            }
        });

        $installer = PluginInstallerFactory::createInstaller(AxytosKaufAufRechnung::class, $container);

        $this->assertInstanceOf(PluginInstaller::class, $installer);
    }
}
