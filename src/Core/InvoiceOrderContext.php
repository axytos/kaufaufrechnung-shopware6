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
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CustomerDataDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\DeliveryAddressDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\InvoiceAddressDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\LogisticianCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\TrackingIdCalculator;
use DateTimeInterface;
use Exception;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class InvoiceOrderContext implements InvoiceOrderContextInterface
{
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var \Shopware\Core\Framework\Context
     */
    private $context;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository
     */
    private $orderEntityRepository;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\CustomerDataDtoFactory
     */
    private $customerDataDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\DeliveryAddressDtoFactory
     */
    private $deliveryAddressDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\InvoiceAddressDtoFactory
     */
    private $invoiceAddressDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\BasketDtoFactory
     */
    private $basketDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketDtoFactory
     */
    private $createInvoiceBasketDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketDtoFactory
     */
    private $refundBasketDtoFactory;
    /**
     * @var \Axytos\ECommerce\DataMapping\DtoToDtoMapper
     */
    private $dtoToDtoMapper;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory
     */
    private $returnPositionModelDtoCollectionFactory;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ValueCalculation\TrackingIdCalculator
     */
    private $trackingIdCalculator;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ValueCalculation\LogisticianCalculator
     */
    private $logisticianCalculator;

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
        ReturnPositionModelDtoCollectionFactory $returnPositionModelDtoCollectionFactory,
        TrackingIdCalculator $trackingIdCalculator,
        LogisticianCalculator $logisticianCalculator
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
        $this->trackingIdCalculator = $trackingIdCalculator;
        $this->logisticianCalculator = $logisticianCalculator;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    private function getOrder(): OrderEntity
    {
        return $this->orderEntityRepository->findOrder($this->orderId, $this->context);
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        $orderEntity = $this->getOrder();
        $orderNumber = $orderEntity->getOrderNumber();

        if (is_null($orderNumber)) {
            throw new Exception("OrderNumber not defined for order with id '{$orderEntity->getId()}'.");
        }

        return $orderNumber;
    }

    /**
     * @return string
     */
    public function getOrderInvoiceNumber()
    {
        $orderEntity = $this->getOrder();
        $documents = $orderEntity->getDocuments();
        if (!is_null($documents)) {
            /** @var DocumentEntity $document */
            foreach ($documents as $document) {
                $documentType = $document->getDocumentType();
                if (!is_null($documentType) && $documentType->getTechnicalName() === 'invoice') {
                    return strval($document->getDocumentNumber());
                }
            }
        }
        return '';
    }

    /**
     * @return \DateTimeInterface
     */
    public function getOrderDateTime()
    {
        $orderEntity = $this->getOrder();
        return $orderEntity->getOrderDateTime();
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\CustomerDataDto
     */
    public function getPersonalData()
    {
        $orderEntity = $this->getOrder();
        return $this->customerDataDtoFactory->create($orderEntity);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto
     */
    public function getInvoiceAddress()
    {
        $orderEntity = $this->getOrder();
        return $this->invoiceAddressDtoFactory->create($orderEntity);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\DeliveryAddressDto
     */
    public function getDeliveryAddress()
    {
        $orderEntity = $this->getOrder();
        return $this->deliveryAddressDtoFactory->create($orderEntity);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\BasketDto
     */
    public function getBasket()
    {
        $orderEntity = $this->getOrder();
        return $this->basketDtoFactory->create($orderEntity);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto
     */
    public function getCreateInvoiceBasket()
    {
        $orderEntity = $this->getOrder();
        return $this->createInvoiceBasketDtoFactory->create($orderEntity);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDtoCollection
     */
    public function getShippingBasketPositions()
    {
        $basketPositions = $this->getBasket()->positions;
        return $this->dtoToDtoMapper->mapDtoCollection($basketPositions, ShippingBasketPositionDtoCollection::class);
    }

    /**
     * @return array<mixed>
     */
    public function getPreCheckResponseData()
    {
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($this->orderId, $this->context);
        return $attributes->getOrderPreCheckResult();
    }

    /**
     * @param array<mixed> $data
     * @return void
     */
    public function setPreCheckResponseData($data)
    {
        $attributes = $this->orderEntityRepository->getAxytosOrderAttributes($this->orderId, $this->context);
        $attributes->setOrderPreCheckResult($data);
        $this->orderEntityRepository->updateAxytosOrderAttributes($this->orderId, $attributes, $this->context);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\RefundBasketDto
     */
    public function getRefundBasket()
    {
        $orderEntity = $this->getOrder();
        return $this->refundBasketDtoFactory->create($orderEntity);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection
     */
    public function getReturnPositions()
    {
        $orderEntity = $this->getOrder();
        return $this->returnPositionModelDtoCollectionFactory->create($orderEntity->getLineItems());
    }

    /**
     * @return float
     */
    public function getDeliveryWeight()
    {
        // for now delivery weight is not important for risk evaluation
        // because different shop systems don't always provide the necessary
        // information to accurately the exact delivery weight for each delivery
        // we decided to return 0 as constant delivery weight
        return 0;
    }
    /**
     * @return string[]
     */
    public function getTrackingIds()
    {
        $orderEntity = $this->getOrder();
        return $this->trackingIdCalculator->calculate($orderEntity);
    }

    /**
     * @return string
     */
    public function getLogistician()
    {
        $orderEntity = $this->getOrder();
        return $this->logisticianCalculator->calculate($orderEntity);
    }
}
