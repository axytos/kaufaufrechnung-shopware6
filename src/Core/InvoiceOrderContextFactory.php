<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
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
use Shopware\Core\Framework\Context;

class InvoiceOrderContextFactory
{
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

    public function getInvoiceOrderContext(string $orderId, Context $context): InvoiceOrderContext
    {
        return new InvoiceOrderContext($orderId, $context, $this->orderEntityRepository, $this->customerDataDtoFactory, $this->deliveryAddressDtoFactory, $this->invoiceAddressDtoFactory, $this->basketDtoFactory, $this->createInvoiceBasketDtoFactory, $this->refundBasketDtoFactory, $this->dtoToDtoMapper, $this->returnPositionModelDtoCollectionFactory, $this->trackingIdCalculator, $this->logisticianCalculator);
    }
}
