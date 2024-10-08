<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Client;

use Axytos\ECommerce\Abstractions\UserAgentInfoProviderInterface;
use Axytos\ECommerce\PackageInfo\ComposerPackageInfoProvider;
use Axytos\KaufAufRechnung\Shopware\Client\UserAgentInfoProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class UserAgentInfoProviderTest extends TestCase
{
    /** @var ComposerPackageInfoProvider&MockObject */
    private $composerPackageInfoProvider;
    /**
     * @var UserAgentInfoProvider
     */
    private $sut;

    public function setUp(): void
    {
        $this->composerPackageInfoProvider = $this->createMock(ComposerPackageInfoProvider::class);
        $this->sut = new UserAgentInfoProvider($this->composerPackageInfoProvider);
    }

    public function test_implements_user_agent_info_provider_interface(): void
    {
        $this->assertInstanceOf(UserAgentInfoProviderInterface::class, $this->sut);
    }

    public function test_get_plugin_name_returns_kauf_auf_rechnung(): void
    {
        $pluginName = $this->sut->getPluginName();

        $this->assertEquals('KaufAufRechnung', $pluginName);
    }

    public function getComposerPackageName(): string
    {
        /** @var string */
        $composerJson = file_get_contents(__DIR__ . '/../../../composer.json');
        /** @var string[] */
        $config = json_decode($composerJson, true);

        return $config['name'];
    }

    public function test_get_plugin_version_returns_version_from_composer(): void
    {
        $expected = 'version';

        $packageName = $this->getComposerPackageName();

        $this->composerPackageInfoProvider
            ->method('getVersion')
            ->with($packageName)
            ->willReturn($expected)
        ;

        $actual = $this->sut->getPluginVersion();

        $this->assertEquals($expected, $actual);
    }

    public function test_get_shop_system_name_returns_shopware(): void
    {
        $shopSystemName = $this->sut->getShopSystemName();

        $this->assertEquals('Shopware', $shopSystemName);
    }

    public function test_get_shop_system_version_returns_version_from_composer(): void
    {
        $expected = 'version';
        $this->composerPackageInfoProvider
            ->method('getVersion')
            ->with('shopware/core')
            ->willReturn($expected)
        ;

        $actual = $this->sut->getShopSystemVersion();

        $this->assertEquals($expected, $actual);
    }
}
