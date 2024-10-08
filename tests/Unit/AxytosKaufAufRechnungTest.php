<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit;

use Axytos\KaufAufRechnung\Shopware\AxytosKaufAufRechnung;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AxytosKaufAufRechnungTest extends TestCase
{
    /**
     * @var AxytosKaufAufRechnung
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new AxytosKaufAufRechnung(true, 'basePath');
    }

    public function test_axytos_kauf_auf_rechnung_can_be_constructed(): void
    {
        $plugin = $this->sut;

        $this->assertNotNull($plugin);
    }
}
