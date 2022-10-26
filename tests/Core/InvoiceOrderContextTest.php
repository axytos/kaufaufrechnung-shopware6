<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Core;

use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
use Axytos\ECommerce\DataTransferObjects\BasketDto;
use Axytos\ECommerce\DataTransferObjects\CustomerDataDto;
use Axytos\ECommerce\DataTransferObjects\DeliveryAddressDto;
use Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketDto;
use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\Shopware\DataMapping\BasketDtoFactory;
use Axytos\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\Shopware\DataMapping\CustomerDataDtoFactory;
use Axytos\Shopware\DataMapping\DeliveryAddressDtoFactory;
use Axytos\Shopware\DataMapping\InvoiceAddressDtoFactory;
use Axytos\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
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
    private Context $context;
    /** @var OrderEntityRepository&MockObject */
    private OrderEntityRepository $orderEntityRepository;
    /** @var CustomerDataDtoFactory&MockObject */
    private CustomerDataDtoFactory $customerDataDtoFactory;
    /** @var DeliveryAddressDtoFactory&MockObject */
    private DeliveryAddressDtoFactory $deliveryAddressDtoFactory;
    /** @var InvoiceAddressDtoFactory&MockObject */
    private InvoiceAddressDtoFactory $invoiceAddressDtoFactory;
    /** @var BasketDtoFactory&MockObject */
    private BasketDtoFactory $basketDtoFactory;
    /** @var CreateInvoiceBasketDtoFactory&MockObject */
    private CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory;
    /** @var RefundBasketDtoFactory&MockObject */
    private RefundBasketDtoFactory $refundBasketDtoFactory;
    /** @var DtoToDtoMapper&MockObject */
    private DtoToDtoMapper $dtoToDtoMapper;
    /** @var ReturnPositionModelDtoCollectionFactory&MockObject */
    private ReturnPositionModelDtoCollectionFactory $returnPositionModelDtoCollectionFactory;

    private InvoiceOrderContext $sut;

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
            $this->returnPositionModelDtoCollectionFactory
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

    public function test_getPreCheckResponseData_returns_precheck_response_data_if_key_exists(): void
    {
        $preCheckResponseData = ['key' => 42];
        $customFields = [
            'axytos_invoice_order_check_response' => $preCheckResponseData
        ];

        $this->orderEntityRepository
            ->method('getCustomFields')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($customFields);

        $actual = $this->sut->getPreCheckResponseData();

        $this->assertSame($preCheckResponseData, $actual);
    }

    public function test_getPreCheckResponseData_returns_empty_array_if_key_does_not_exists(): void
    {
        $this->orderEntityRepository
            ->method('getCustomFields')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn([]);

        $actual = $this->sut->getPreCheckResponseData();

        $this->assertSame([], $actual);
    }

    public function test_setPreCheckResponseData_saves_precheck_response_data_as_custom_field_for_order(): void
    {
        $preCheckResponseData = ['key' => 42];
        $customFields = [
            'axytos_invoice_order_check_response' => $preCheckResponseData
        ];

        $this->orderEntityRepository
            ->expects($this->once())
            ->method('updateCustomFields')
            ->with(self::ORDER_ID, $customFields, $this->context);

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
