<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface;
use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\KaufAufRechnung\Shopware\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CustomerDataDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\DeliveryAddressDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\InvoiceAddressDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\LogisticianCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\TrackingIdCalculator;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;

/**
 * @internal
 */
class InvoiceOrderContextFactoryTest extends TestCase
{
    /**
     * @var InvoiceOrderContextFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new InvoiceOrderContextFactory(
            $this->createMock(OrderEntityRepository::class),
            $this->createMock(CustomerDataDtoFactory::class),
            $this->createMock(DeliveryAddressDtoFactory::class),
            $this->createMock(InvoiceAddressDtoFactory::class),
            $this->createMock(BasketDtoFactory::class),
            $this->createMock(CreateInvoiceBasketDtoFactory::class),
            $this->createMock(RefundBasketDtoFactory::class),
            $this->createMock(DtoToDtoMapper::class),
            $this->createMock(ReturnPositionModelDtoCollectionFactory::class),
            $this->createMock(TrackingIdCalculator::class),
            $this->createMock(LogisticianCalculator::class)
        );
    }

    public function test_get_invoice_order_context(): void
    {
        $orderId = 'orderId';
        $context = $this->createMock(Context::class);

        $actual = $this->sut->getInvoiceOrderContext($orderId, $context);

        $this->assertInstanceOf(InvoiceOrderContextInterface::class, $actual);
    }
}
