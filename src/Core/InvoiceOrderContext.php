<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface;
use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
use Axytos\ECommerce\DataTransferObjects\BasketDto;
use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto;
use Axytos\ECommerce\DataTransferObjects\CustomerDataDto;
use Axytos\ECommerce\DataTransferObjects\DeliveryAddressDto;
use Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto;
use Axytos\ECommerce\DataTransferObjects\RefundBasketDto;
use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection;
use Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDtoCollection;
use Axytos\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\Shopware\DataMapping\CustomerDataDtoFactory;
use Axytos\Shopware\DataMapping\DeliveryAddressDtoFactory;
use Axytos\Shopware\DataMapping\InvoiceAddressDtoFactory;
use Axytos\Shopware\DataMapping\BasketDtoFactory;
use Axytos\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use DateTimeInterface;
use Exception;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class InvoiceOrderContext implements InvoiceOrderContextInterface
{
    private string $orderId;
    private Context $context;
    private OrderEntityRepository $orderEntityRepository;
    private CustomerDataDtoFactory $customerDataDtoFactory;
    private DeliveryAddressDtoFactory $deliveryAddressDtoFactory;
    private InvoiceAddressDtoFactory $invoiceAddressDtoFactory;
    private BasketDtoFactory $basketDtoFactory;
    private CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory;
    private RefundBasketDtoFactory $refundBasketDtoFactory;
    private DtoToDtoMapper $dtoToDtoMapper;
    private ReturnPositionModelDtoCollectionFactory $returnPositionModelDtoCollectionFactory;

    private string $invoiceNumber;

    public function __construct(
        string $orderId,
        Context $context,
        OrderEntityRepository $orderEntityRepository,
        CustomerDataDtoFactory $customerDataDtoFactory,
        DeliveryAddressDtoFactory $deliveryAddressDtoFactory,
        InvoiceAddressDtoFactory $invoiceAddressDtoFactory,
        BasketDtoFactory $basketDtoFactory,
        CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory,
        RefundBasketDtoFactory $refundBasketDtoFactory,
        DtoToDtoMapper $dtoToDtoMapper,
        ReturnPositionModelDtoCollectionFactory $returnPositionModelDtoCollectionFactory
    ) {
        $this->orderId = $orderId;
        $this->context = $context;
        $this->orderEntityRepository = $orderEntityRepository;
        $this->customerDataDtoFactory = $customerDataDtoFactory;
        $this->deliveryAddressDtoFactory = $deliveryAddressDtoFactory;
        $this->invoiceAddressDtoFactory = $invoiceAddressDtoFactory;
        $this->basketDtoFactory = $basketDtoFactory;
        $this->createInvoiceBasketDtoFactory = $createInvoiceBasketDtoFactory;
        $this->refundBasketDtoFactory = $refundBasketDtoFactory;
        $this->dtoToDtoMapper = $dtoToDtoMapper;
        $this->returnPositionModelDtoCollectionFactory = $returnPositionModelDtoCollectionFactory;
    }

    private function getOrder(): OrderEntity
    {
        return $this->orderEntityRepository->findOrder($this->orderId, $this->context);
    }

    public function getOrderNumber(): string
    {
        $orderEntity = $this->getOrder();
        $orderNumber = $orderEntity->getOrderNumber();

        if (is_null($orderNumber)) {
            throw new Exception("OrderNumber not defined for order with id '{$orderEntity->getId()}'.");
        }

        return $orderNumber;
    }

    public function getOrderInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function setOrderInvoiceNumber(string $invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getOrderDateTime(): DateTimeInterface
    {
        $orderEntity = $this->getOrder();
        return $orderEntity->getOrderDateTime();
    }

    public function getPersonalData(): CustomerDataDto
    {
        $orderEntity = $this->getOrder();
        return $this->customerDataDtoFactory->create($orderEntity);
    }

    public function getInvoiceAddress(): InvoiceAddressDto
    {
        $orderEntity = $this->getOrder();
        return $this->invoiceAddressDtoFactory->create($orderEntity);
    }

    public function getDeliveryAddress(): DeliveryAddressDto
    {
        $orderEntity = $this->getOrder();
        return $this->deliveryAddressDtoFactory->create($orderEntity);
    }

    public function getBasket(): BasketDto
    {
        $orderEntity = $this->getOrder();
        return $this->basketDtoFactory->create($orderEntity);
    }

    public function getCreateInvoiceBasket(): CreateInvoiceBasketDto
    {
        $orderEntity = $this->getOrder();
        return $this->createInvoiceBasketDtoFactory->create($orderEntity);
    }

    public function getShippingBasketPositions(): ShippingBasketPositionDtoCollection
    {
        $basketPositions = $this->getBasket()->positions;
        return $this->dtoToDtoMapper->mapDtoCollection($basketPositions, ShippingBasketPositionDtoCollection::class);
    }

    public function getPreCheckResponseData(): array
    {
        $customFields = $this->orderEntityRepository->getCustomFields($this->orderId, $this->context);

        if (!array_key_exists('axytos_invoice_order_check_response', $customFields)) {
            return [];
        }

        return $customFields['axytos_invoice_order_check_response'];
    }

    public function setPreCheckResponseData(array $data): void
    {
        $customFields = ['axytos_invoice_order_check_response' => $data];
        $this->orderEntityRepository->updateCustomFields($this->orderId, $customFields, $this->context);
    }

    public function getRefundBasket(): RefundBasketDto
    {
        $orderEntity = $this->getOrder();
        return $this->refundBasketDtoFactory->create($orderEntity);
    }

    public function getReturnPositions(): ReturnPositionModelDtoCollection
    {
        $orderEntity = $this->getOrder();
        return $this->returnPositionModelDtoCollectionFactory->create($orderEntity->getLineItems());
    }
}
