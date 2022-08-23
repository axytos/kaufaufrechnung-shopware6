<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface;
use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\Shopware\DataMapping\BasketDtoFactory;
use Axytos\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\Shopware\DataMapping\CustomerDataDtoFactory;
use Axytos\Shopware\DataMapping\DeliveryAddressDtoFactory;
use Axytos\Shopware\DataMapping\InvoiceAddressDtoFactory;
use Axytos\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;

class InvoiceOrderContextFactoryTest extends TestCase
{
    private InvoiceOrderContextFactory $sut;

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
            $this->createMock(ReturnPositionModelDtoCollectionFactory::class)
        );
    }

    public function test_getInvoiceOrderContext(): void
    {
        $orderId = 'orderId';
        $context = $this->createMock(Context::class);

        $actual = $this->sut->getInvoiceOrderContext($orderId, $context);

        $this->assertInstanceOf(InvoiceOrderContextInterface::class, $actual);
    }

}
