<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit;

use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfigurationValueNames;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @internal
 */
class PluginConfigurationTest extends TestCase
{
    /** @var SystemConfigService&MockObject */
    private $systemConfigService;
    /**
     * @var PluginConfiguration
     */
    private $sut;

    public function setUp(): void
    {
        $this->systemConfigService = $this->createMock(SystemConfigService::class);

        $this->sut = new PluginConfiguration($this->systemConfigService);
    }

    public function test_get_api_host_returns_api_host_from_configuration(): void
    {
        $expected = 'apiHost';

        $this->systemConfigService
            ->method('getString')
            ->with(PluginConfigurationValueNames::API_HOST)
            ->willReturn($expected)
        ;

        $actual = $this->sut->getApiHost();

        $this->assertEquals($expected, $actual);
    }

    public function test_get_api_key_returns_api_key_from_configuration(): void
    {
        $expected = 'apiKey';

        $this->systemConfigService
            ->method('getString')
            ->with(PluginConfigurationValueNames::API_KEY)
            ->willReturn($expected)
        ;

        $actual = $this->sut->getApiKey();

        $this->assertEquals($expected, $actual);
    }

    public function test_get_client_secret_returns_client_secret_from_configuration(): void
    {
        $expected = 'client_secret';

        $this->systemConfigService
            ->method('getString')
            ->with(PluginConfigurationValueNames::CLIENT_SECRET)
            ->willReturn($expected)
        ;

        $actual = $this->sut->getClientSecret();

        $this->assertEquals($expected, $actual);
    }
}
