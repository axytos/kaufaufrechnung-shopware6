<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit;

use Axytos\KaufAufRechnung\Shopware\AxytosKaufAufRechnung;
use PHPUnit\Framework\TestCase;

class AxytosKaufAufRechnungTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\AxytosKaufAufRechnung
     */
    private $sut;

    public function setup(): void
    {
        $this->sut = new AxytosKaufAufRechnung(true, 'basePath');
    }

    public function test_AxytosKaufAufRechnung_can_be_constructed(): void
    {
        $plugin = $this->sut;

        $this->assertNotNull($plugin);
    }
}
