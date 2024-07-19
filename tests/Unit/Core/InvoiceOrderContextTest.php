<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Core;

use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
use Axytos\ECommerce\DataTransferObjects\BasketDto;
use Axytos\ECommerce\DataTransferObjects\CustomerDataDto;
use Axytos\ECommerce\DataTransferObjects\DeliveryAddressDto;
use Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketDto;
use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\Data\AxytosOrderAttributesEntity;
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
use DateTime;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class InvoiceOrderContextTest extends TestCase
{
    const ORDER_ID = 'orderId';
    /** @var Context&MockObject */
    private $context;
    /** @var OrderEntityRepository&MockObject */
    private $orderEntityRepository;
    /** @var CustomerDataDtoFactory&MockObject */
    private $customerDataDtoFactory;
    /** @var DeliveryAddressDtoFactory&MockObject */
    private $deliveryAddressDtoFactory;
    /** @var InvoiceAddressDtoFactory&MockObject */
    private $invoiceAddressDtoFactory;
    /** @var BasketDtoFactory&MockObject */
    private $basketDtoFactory;
    /** @var CreateInvoiceBasketDtoFactory&MockObject */
    private $createInvoiceBasketDtoFactory;
    /** @var RefundBasketDtoFactory&MockObject */
    private $refundBasketDtoFactory;
    /** @var DtoToDtoMapper&MockObject */
    private $dtoToDtoMapper;
    /** @var ReturnPositionModelDtoCollectionFactory&MockObject */
    private $returnPositionModelDtoCollectionFactory;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext
     */
    private $sut;

    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->orderEntityRepository = $this->createMock(OrderEntityRepository::class);
        $this->customerDataDtoFactory = $this->createMock(CustomerDataDtoFactory::class);
        $this->deliveryAddressDtoFactory = $this->createMock(DeliveryAddressDtoFactory::class);
        $this->invoiceAddressDtoFactory = $this->createMock(InvoiceAddressDtoFactory::class);
        $this->basketDtoFactory = $this->createMock(BasketDtoFactory::class);
        $this->createInvoiceBasketDtoFactory = $this->createMock(CreateInvoiceBasketDtoFactory::class);
        $this->refundBasketDtoFactory = $this->createMock(RefundBasketDtoFactory::class);
        $this->dtoToDtoMapper = $this->createMock(DtoToDtoMapper::class);
        $this->returnPositionModelDtoCollectionFactory = $this->createMock(ReturnPositionModelDtoCollectionFactory::class);

        $this->sut = new InvoiceOrderContext(
            self::ORDER_ID,
            $this->context,
            $this->orderEntityRepository,
            $this->customerDataDtoFactory,
            $this->deliveryAddressDtoFactory,
            $this->invoiceAddressDtoFactory,
            $this->basketDtoFactory,
            $this->createInvoiceBasketDtoFactory,
            $this->refundBasketDtoFactory,
            $this->dtoToDtoMapper,
            $this->returnPositionModelDtoCollectionFactory,
            $this->createMock(TrackingIdCalculator::class),
            $this->createMock(LogisticianCalculator::class)
        );
    }

    private function setUpOrderEntityRepository(OrderEntity $orderEntity): void
    {
        $this->orderEntityRepository
            ->method('findOrder')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($orderEntity);
    }

    public function test_getOrderNumber_returns_order_number(): void
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getOrderNumber')->willReturn('orderNumber');

        $this->setUpOrderEntityRepository($orderEntity);

        $actual = $this->sut->getOrderNumber();

        $this->assertSame($orderEntity->getOrderNumber(), $actual);
    }

    public function test_getOrderNumber_throws_Exception_when_order_number_is_null(): void
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getOrderNumber')->willReturn(null);

        $this->setUpOrderEntityRepository($orderEntity);

        $this->expectException(Exception::class);
        $this->sut->getOrderNumber();
    }

    public function test_getOrderDateTime_returns_order_date_time(): void
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->method('getOrderDateTime')->willReturn(new DateTime());

        $this->setUpOrderEntityRepository($orderEntity);

        $actual = $this->sut->getOrderDateTime();

        $this->assertSame($orderEntity->getOrderDateTime(), $actual);
    }

    public function test_getPersonalData_creates_CustomerDataDto(): void
    {
        $orderEntity = $this->createMock(OrderEntity::class);
        $customerDataDto = $this->createMock(CustomerDataDto::class);

        $this->setUpOrderEntityRepository($orderEntity);

        $this->customerDataDtoFactory
            ->method('create')
            ->with($orderEntity)
            ->willReturn($customerDataDto);

        $actual = $this->sut->getPersonalData();

        $this->assertSame($customerDataDto, $actual);
    }

    public function test_getInvoiceAddress_creates_InvoiceAddressDto(): void
    {
        $orderEntity = $this->createMock(OrderEntity::class);
        $invoiceAddressDto = $this->createMock(InvoiceAddressDto::class);

        $this->setUpOrderEntityRepository($orderEntity);

        $this->invoiceAddressDtoFactory
            ->method('create')
            ->with($orderEntity)
            ->willReturn($invoiceAddressDto);

        $actual = $this->sut->getInvoiceAddress();

        $this->assertSame($invoiceAddressDto, $actual);
    }

    public function test_getDeliveryAddress_creates_DeliveryAddressDto(): void
    {
        $orderEntity = $this->createMock(OrderEntity::class);
        $deliveryAddressDto = $this->createMock(DeliveryAddressDto::class);

        $this->setUpOrderEntityRepository($orderEntity);

        $this->deliveryAddressDtoFactory
            ->method('create')
            ->with($orderEntity)
            ->willReturn($deliveryAddressDto);

        $actual = $this->sut->getDeliveryAddress();

        $this->assertSame($deliveryAddressDto, $actual);
    }

    public function test_getBasket_creates_BasketDto(): void
    {
        $orderEntity = $this->createMock(OrderEntity::class);
        $basketDto = $this->createMock(BasketDto::class);

        $this->setUpOrderEntityRepository($orderEntity);

        $this->basketDtoFactory
            ->method('create')
            ->with($orderEntity)
            ->willReturn($basketDto);

        $actual = $this->sut->getBasket();

        $this->assertSame($basketDto, $actual);
    }

    public function test_getPreCheckResponseData_returns_precheck_response_data(): void
    {
        $preCheckResponseData = ['key' => 42];
        $attributes = $this->createMock(AxytosOrderAttributesEntity::class);

        $attributes
            ->method('getOrderPreCheckResult')
            ->willReturn($preCheckResponseData);

        $this->orderEntityRepository
            ->method('getAxytosOrderAttributes')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($attributes);

        $actual = $this->sut->getPreCheckResponseData();

        $this->assertSame($preCheckResponseData, $actual);
    }

    public function test_setPreCheckResponseData_saves_precheck_response_data_in_custom_extension_for_order(): void
    {
        $preCheckResponseData = ['key' => 42];
        $attributes = $this->createMock(AxytosOrderAttributesEntity::class);

        $attributes
            ->method('getOrderPreCheckResult')
            ->willReturn($preCheckResponseData);

        $this->orderEntityRepository
            ->method('getAxytosOrderAttributes')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($attributes);

        $attributes
            ->expects($this->once())
            ->method('setOrderPreCheckResult')
            ->with($preCheckResponseData);

        $this->orderEntityRepository
            ->expects($this->once())
            ->method('updateAxytosOrderAttributes')
            ->with(self::ORDER_ID, $attributes, $this->context);

        $this->sut->setPreCheckResponseData($preCheckResponseData);
    }

    public function test_getBasket_creates_RefundBasketDto(): void
    {
        $orderEntity = $this->createMock(OrderEntity::class);
        $refundBasketDto = $this->createMock(RefundBasketDto::class);

        $this->setUpOrderEntityRepository($orderEntity);

        $this->refundBasketDtoFactory
            ->method('create')
            ->with($orderEntity)
            ->willReturn($refundBasketDto);

        $actual = $this->sut->getRefundBasket();

        $this->assertSame($refundBasketDto, $actual);
    }

    public function test_getReturnPositions_creates_ReturnPositionModelDtoCollection(): void
    {
        /** @var OrderEntity&MockObject */
        $orderEntity = $this->createMock(OrderEntity::class);
        $orderLineItems = $this->createMock(OrderLineItemCollection::class);
        $returnPositions = $this->createMock(ReturnPositionModelDtoCollection::class);

        $orderEntity->method('getLineItems')->willReturn($orderLineItems);

        $this->setUpOrderEntityRepository($orderEntity);

        $this->returnPositionModelDtoCollectionFactory
            ->method('create')
            ->with($orderLineItems)
            ->willReturn($returnPositions);

        $actual = $this->sut->getReturnPositions();

        $this->assertSame($returnPositions, $actual);
    }
}
