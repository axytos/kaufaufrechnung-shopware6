<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests;

use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfigurationValueNames;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PluginConfigurationTest extends TestCase
{
    /** @var SystemConfigService&MockObject $systemConfigService */
    private SystemConfigService $systemConfigService;
    private PluginConfiguration $sut;

    public function setUp(): void
    {
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        
        $this->sut = new PluginConfiguration($this->systemConfigService);
    }

    public function test_getApiHost_returns_api_host_from_configuration(): void
    {
        $expected = 'apiHost';

        $this->systemConfigService
            ->method('getString')
            ->with(PluginConfigurationValueNames::API_HOST)
            ->willReturn($expected);

        $actual = $this->sut->getApiHost();

        $this->assertEquals($expected, $actual);
    }

    public function test_getApiKey_returns_api_key_from_configuration(): void
    {
        $expected = 'apiKey';

        $this->systemConfigService
            ->method('getString')
            ->with(PluginConfigurationValueNames::API_KEY)
            ->willReturn($expected);

        $actual = $this->sut->getApiKey();

        $this->assertEquals($expected, $actual);
    }

    public function test_getClientSecret_returns_client_secret_from_configuration(): void
    {
        $expected = 'client_secret';

        $this->systemConfigService
            ->method('getString')
            ->with(PluginConfigurationValueNames::CLIENT_SECRET)
            ->willReturn($expected);

        $actual = $this->sut->getClientSecret();

        $this->assertEquals($expected, $actual);
    }
}