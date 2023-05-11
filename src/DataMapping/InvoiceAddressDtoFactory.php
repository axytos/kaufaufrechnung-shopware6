<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto;
use Shopware\Core\Checkout\Order\OrderEntity;

class InvoiceAddressDtoFactory
{
    public function create(OrderEntity $orderEntity): InvoiceAddressDto
    {
        $invoiceAddress = new InvoiceAddressDto();

        $billingAddress = $orderEntity->getBillingAddress();

        if (!is_null($billingAddress)) {
            $invoiceAddress->addressLine1 = $billingAddress->getStreet();
            $invoiceAddress->city = $billingAddress->getCity();
            $invoiceAddress->company = $billingAddress->getCompany();
            $invoiceAddress->firstname = $billingAddress->getFirstName();
            $invoiceAddress->lastname = $billingAddress->getLastName();
            $invoiceAddress->zipCode = $billingAddress->getZipcode();
            $invoiceAddress->vatId = $billingAddress->getVatId();

            $country = $billingAddress->getCountry();
            if (!is_null($country) && !is_null($country->getIso())) {
                $invoiceAddress->country = $country->getIso();
            }

            $countryState = $billingAddress->getCountryState();
            if (!is_null($countryState)) {
                $invoiceAddress->region = $countryState->getName();
            }

            $salutation = $billingAddress->getSalutation();
            if (!is_null($salutation)) {
                $invoiceAddress->salutation = $salutation->getDisplayName();
            }
        }

        return $invoiceAddress;
    }
}
