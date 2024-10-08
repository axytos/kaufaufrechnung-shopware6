<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Client;

use Axytos\ECommerce\Abstractions\ApiHostProviderInterface;
use Axytos\KaufAufRechnung\Shopware\Client\ApiHostProvider;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ApiHostProviderTest extends TestCase
{
    /** @var PluginConfiguration&MockObject */
    private $pluginConfiguration;

    /**
     * @var ApiHostProvider
     */
    private $sut;

    public function setUp(): void
    {
        $this->pluginConfiguration = $this->createMock(PluginConfiguration::class);

        $this->sut = new ApiHostProvider(
            $this->pluginConfiguration
        );
    }

    public function test_implements_api_host_provider_interface(): void
    {
        $this->assertInstanceOf(ApiHostProviderInterface::class, $this->sut);
    }

    public function test_get_api_host_returns_sandbox_by_default(): void
    {
        $this->pluginConfiguration
            ->method('getApiHost')
            ->willReturn('something else')
        ;

        $actual = $this->sut->getApiHost();

        $this->assertSame(ApiHostProviderInterface::SANDBOX, $actual);
    }

    public function test_get_api_host_returns_sandbox_for_sandbox_option_from_configuration(): void
    {
        $this->pluginConfiguration
            ->method('getApiHost')
            ->willReturn('APIHOST_SANDBOX')
        ;

        $actual = $this->sut->getApiHost();

        $this->assertSame(ApiHostProviderInterface::SANDBOX, $actual);
    }

    public function test_get_api_host_returns_live_for_live_option_from_configuration(): void
    {
        $this->pluginConfiguration
            ->method('getApiHost')
            ->willReturn('APIHOST_LIVE')
        ;

        $actual = $this->sut->getApiHost();

        $this->assertSame(ApiHostProviderInterface::LIVE, $actual);
    }
}
