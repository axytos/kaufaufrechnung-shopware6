<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\KaufAufRechnung\Shopware\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\CustomerDataDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\DeliveryAddressDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\InvoiceAddressDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\RefundPartialBasketDtoFactory;
use Axytos\KaufAufRechnung\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\LogisticianCalculator;
use Axytos\KaufAufRechnung\Shopware\ValueCalculation\TrackingIdCalculator;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class InvoiceOrderContextFactory
{
    /**
     * @var OrderEntityRepository
     */
    private $orderEntityRepository;
    /**
     * @var CustomerDataDtoFactory
     */
    private $customerDataDtoFactory;
    /**
     * @var DeliveryAddressDtoFactory
     */
    private $deliveryAddressDtoFactory;
    /**
     * @var InvoiceAddressDtoFactory
     */
    private $invoiceAddressDtoFactory;
    /**
     * @var BasketDtoFactory
     */
    private $basketDtoFactory;
    /**
     * @var CreateInvoiceBasketDtoFactory
     */
    private $createInvoiceBasketDtoFactory;
    /**
     * @var RefundBasketDtoFactory
     */
    private $refundBasketDtoFactory;
    /**
     * @var RefundPartialBasketDtoFactory
     */
    private $refundPartialBasketDtoFactory;
    /**
     * @var DtoToDtoMapper
     */
    private $dtoToDtoMapper;
    /**
     * @var ReturnPositionModelDtoCollectionFactory
     */
    private $returnPositionModelDtoCollectionFactory;
    /**
     * @var TrackingIdCalculator
     */
    private $trackingIdCalculator;
    /**
     * @var LogisticianCalculator
     */
    private $logisticianCalculator;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        OrderEntityRepository $orderEntityRepository,
        CustomerDataDtoFactory $customerDataDtoFactory,
        DeliveryAddressDtoFactory $deliveryAddressDtoFactory,
        InvoiceAddressDtoFactory $invoiceAddressDtoFactory,
        BasketDtoFactory $basketDtoFactory,
        CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory,
        RefundBasketDtoFactory $refundBasketDtoFactory,
        RefundPartialBasketDtoFactory $refundPartialBasketDtoFactory,
        DtoToDtoMapper $dtoToDtoMapper,
        ReturnPositionModelDtoCollectionFactory $returnPositionModelDtoCollectionFactory,
        TrackingIdCalculator $trackingIdCalculator,
        LogisticianCalculator $logisticianCalculator,
        SystemConfigService $systemConfigService
    ) {
        $this->orderEntityRepository = $orderEntityRepository;
        $this->customerDataDtoFactory = $customerDataDtoFactory;
        $this->deliveryAddressDtoFactory = $deliveryAddressDtoFactory;
        $this->invoiceAddressDtoFactory = $invoiceAddressDtoFactory;
        $this->basketDtoFactory = $basketDtoFactory;
        $this->createInvoiceBasketDtoFactory = $createInvoiceBasketDtoFactory;
        $this->refundBasketDtoFactory = $refundBasketDtoFactory;
        $this->refundPartialBasketDtoFactory = $refundPartialBasketDtoFactory;
        $this->dtoToDtoMapper = $dtoToDtoMapper;
        $this->returnPositionModelDtoCollectionFactory = $returnPositionModelDtoCollectionFactory;
        $this->trackingIdCalculator = $trackingIdCalculator;
        $this->logisticianCalculator = $logisticianCalculator;
        $this->systemConfigService = $systemConfigService;
    }

    public function getInvoiceOrderContext(string $orderId, Context $context): InvoiceOrderContext
    {
        return new InvoiceOrderContext($orderId, $context, $this->orderEntityRepository, $this->customerDataDtoFactory, $this->deliveryAddressDtoFactory, $this->invoiceAddressDtoFactory, $this->basketDtoFactory, $this->createInvoiceBasketDtoFactory, $this->refundBasketDtoFactory, $this->refundPartialBasketDtoFactory, $this->dtoToDtoMapper, $this->returnPositionModelDtoCollectionFactory, $this->trackingIdCalculator, $this->logisticianCalculator, $this->systemConfigService);
    }
}
