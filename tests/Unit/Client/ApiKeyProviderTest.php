<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Client;

use Axytos\ECommerce\Abstractions\ApiKeyProviderInterface;
use Axytos\KaufAufRechnung\Shopware\Client\ApiKeyProvider;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ApiKeyProviderTest extends TestCase
{
    /** @var PluginConfiguration&MockObject */
    private $pluginConfiguration;
    /**
     * @var ApiKeyProvider
     */
    private $sut;

    public function setUp(): void
    {
        $this->pluginConfiguration = $this->createMock(PluginConfiguration::class);

        $this->sut = new ApiKeyProvider(
            $this->pluginConfiguration
        );
    }

    public function test_implements_api_key_provider_interface(): void
    {
        $this->assertInstanceOf(ApiKeyProviderInterface::class, $this->sut);
    }

    public function test_get_api_key_returns_api_key_from_configuration(): void
    {
        $expected = 'apikey';
        $this->pluginConfiguration
            ->method('getApiKey')
            ->willReturn($expected)
        ;

        $actual = $this->sut->getApiKey();

        $this->assertSame($expected, $actual);
    }
}
