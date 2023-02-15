<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit;

use Axytos\KaufAufRechnung\Shopware\Installer\DefaultPluginIdProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefaultPluginIdProviderTest extends TestCase
{
    const PLUGIN_CLASS_NAME = 'PLUGIN_CLASS_NAME';
    const PLUGIN_ID = 'PLUGIN_ID';

    /** @var ContainerInterface&MockObject */
    private $container;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Installer\DefaultPluginIdProvider
     */
    private $sut;

    /** @var Context&MockObject */
    private $context;

    /** @var PluginIdProvider&MockObject */
    private $pluginIdProvider;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->sut = new DefaultPluginIdProvider($this->container, self::PLUGIN_CLASS_NAME);

        $this->context = $this->createMock(Context::class);
        $this->pluginIdProvider = $this->createMock(PluginIdProvider::class);

        $this->setUpContainer();
    }

    private function setUpContainer(): void
    {
        $this->container
            ->method('get')
            ->with(PluginIdProvider::class)
            ->willReturn($this->pluginIdProvider);
    }

    private function setUpShopwarePluginIdProvider(): void
    {
        $this->pluginIdProvider
            ->method('getPluginIdByBaseClass')
            ->with(self::PLUGIN_CLASS_NAME, $this->context)
            ->willReturn(self::PLUGIN_ID);
    }

    public function test_getPluginId(): void
    {
        $this->setUpShopwarePluginIdProvider();

        $actual = $this->sut->getPluginId($this->context);

        $this->assertEquals(self::PLUGIN_ID, $actual);
    }
}
