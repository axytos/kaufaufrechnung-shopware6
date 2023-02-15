<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Client;

use Axytos\ECommerce\Abstractions\ApiHostProviderInterface;
use Axytos\KaufAufRechnung\Shopware\Client\ApiHostProvider;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiHostProviderTest extends TestCase
{
    /** @var PluginConfiguration&MockObject */
    private $pluginConfiguration;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Client\ApiHostProvider
     */
    private $sut;

    public function setUp(): void
    {
        $this->pluginConfiguration = $this->createMock(PluginConfiguration::class);

        $this->sut = new ApiHostProvider(
            $this->pluginConfiguration
        );
    }

    public function test_implements_ApiHostProviderInterface(): void
    {
        $this->assertInstanceOf(ApiHostProviderInterface::class, $this->sut);
    }

    public function test_getApiHost_returns_api_key_from_configuration(): void
    {
        $expected = 'apihost';
        $this->pluginConfiguration
            ->method('getApiHost')
            ->willReturn($expected);

        $actual = $this->sut->getApiHost();

        $this->assertSame($expected, $actual);
    }
}
