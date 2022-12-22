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

        if ($billingAddress) {
            $invoiceAddress->addressLine1 = $billingAddress->getStreet();
            $invoiceAddress->city = $billingAddress->getCity();
            $invoiceAddress->company = $billingAddress->getCompany();
            $invoiceAddress->firstname = $billingAddress->getFirstName();
            $invoiceAddress->lastname = $billingAddress->getLastName();
            $invoiceAddress->zipCode = $billingAddress->getZipcode();
            $invoiceAddress->vatId = $billingAddress->getVatId();

            $country = $billingAddress->getCountry();
            if ($country && $country->getIso()) {
                $invoiceAddress->country = $country->getIso();
            }

            $countryState = $billingAddress->getCountryState();
            if ($countryState) {
                $invoiceAddress->region = $countryState->getName();
            }

            $salutation = $billingAddress->getSalutation();
            if ($salutation) {
                $invoiceAddress->salutation = $salutation->getDisplayName();
            }
        }

        return $invoiceAddress;
    }
}
