<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Client;

use Axytos\ECommerce\Abstractions\ApiKeyProviderInterface;
use Axytos\KaufAufRechnung\Shopware\Client\ApiKeyProvider;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ApiKeyProviderTest extends TestCase
{
    /** @var PluginConfiguration&MockObject $pluginConfiguration */
    private $pluginConfiguration;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Client\ApiKeyProvider
     */
    private $sut;

    public function setUp(): void
    {
        $this->pluginConfiguration = $this->createMock(PluginConfiguration::class);

        $this->sut = new ApiKeyProvider(
            $this->pluginConfiguration
        );
    }

    public function test_implements_ApiKeyProviderInterface(): void
    {
        $this->assertInstanceOf(ApiKeyProviderInterface::class, $this->sut);
    }

    public function test_getApiKey_returns_api_key_from_configuration(): void
    {
        $expected = 'apikey';
        $this->pluginConfiguration
            ->method('getApiKey')
            ->willReturn($expected);

        $actual = $this->sut->getApiKey();

        $this->assertSame($expected, $actual);
    }
}
