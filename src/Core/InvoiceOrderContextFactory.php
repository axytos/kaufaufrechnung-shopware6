<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface;
use Axytos\ECommerce\DataMapping\DtoToDtoMapper;
use Axytos\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\Shopware\DataMapping\CustomerDataDtoFactory;
use Axytos\Shopware\DataMapping\DeliveryAddressDtoFactory;
use Axytos\Shopware\DataMapping\InvoiceAddressDtoFactory;
use Axytos\Shopware\DataMapping\BasketDtoFactory;
use Axytos\Shopware\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\Shopware\DataMapping\RefundBasketDtoFactory;
use Axytos\Shopware\DataMapping\ReturnPositionModelDtoCollectionFactory;
use Shopware\Core\Framework\Context;

class InvoiceOrderContextFactory
{
    private OrderEntityRepository $orderEntityRepository;
    private CustomerDataDtoFactory $customerDataDtoFactory;
    private DeliveryAddressDtoFactory $deliveryAddressDtoFactory;
    private InvoiceAddressDtoFactory $invoiceAddressDtoFactory;
    private BasketDtoFactory $basketDtoFactory;
    private CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory;
    private RefundBasketDtoFactory $refundBasketDtoFactory;
    private DtoToDtoMapper $dtoToDtoMapper;
    private ReturnPositionModelDtoCollectionFactory $returnPositionModelDtoCollectionFactory;

    public function __construct(
        OrderEntityRepository $orderEntityRepository,
        CustomerDataDtoFactory $customerDataDtoFactory,
        DeliveryAddressDtoFactory $deliveryAddressDtoFactory,
        InvoiceAddressDtoFactory $invoiceAddressDtoFactory,
        BasketDtoFactory $basketDtoFactory,
        CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory,
        RefundBasketDtoFactory $refundBasketDtoFactory,
        DtoToDtoMapper $dtoToDtoMapper,
        ReturnPositionModelDtoCollectionFactory $returnPositionModelDtoCollectionFactory)
    {
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

    public function getInvoiceOrderContext(string $orderId, Context $context): InvoiceOrderContextInterface
    {
        return new InvoiceOrderContext(
            $orderId, 
            $context, 
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
}
