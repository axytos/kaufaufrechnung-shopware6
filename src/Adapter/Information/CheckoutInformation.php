<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\CheckoutInformationInterface;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\Checkout\Basket;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\Checkout\Customer;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\Checkout\DeliveryAddress;
use Axytos\KaufAufRechnung\Shopware\Adapter\Information\Checkout\InvoiceAddress;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;

class CheckoutInformation implements CheckoutInformationInterface
{
    /**
     * @var InvoiceOrderContext
     */
    private $invoiceOrderContext;

    public function __construct(InvoiceOrderContext $invoiceOrderContext)
    {
        $this->invoiceOrderContext = $invoiceOrderContext;
    }

    public function getOrderNumber()
    {
        return $this->invoiceOrderContext->getOrderNumber();
    }

    public function getCustomer()
    {
        $dto = $this->invoiceOrderContext->getPersonalData();

        return new Customer($dto);
    }

    public function getInvoiceAddress()
    {
        $dto = $this->invoiceOrderContext->getInvoiceAddress();

        return new InvoiceAddress($dto);
    }

    public function getDeliveryAddress()
    {
        $dto = $this->invoiceOrderContext->getDeliveryAddress();

        return new DeliveryAddress($dto);
    }

    public function getBasket()
    {
        $dto = $this->invoiceOrderContext->getBasket();

        return new Basket($dto);
    }

    public function savePreCheckResponseData($data)
    {
        $this->invoiceOrderContext->setPreCheckResponseData($data);
    }

    public function getPreCheckResponseData()
    {
        return $this->invoiceOrderContext->getPreCheckResponseData();
    }
}
