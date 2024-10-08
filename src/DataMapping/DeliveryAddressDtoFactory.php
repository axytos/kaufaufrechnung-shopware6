<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\DeliveryAddressDto;
use Shopware\Core\Checkout\Order\OrderEntity;

class DeliveryAddressDtoFactory
{
    public function create(OrderEntity $orderEntity): DeliveryAddressDto
    {
        $deliveryAddress = new DeliveryAddressDto();

        $deliveries = $orderEntity->getDeliveries();

        if (!is_null($deliveries)) {
            $deliveryElements = $deliveries->getElements();

            if ([] !== $deliveryElements) {
                reset($deliveryElements);
                $deliveryElement = $deliveryElements[key($deliveryElements)];

                $shippingOrderAddress = $deliveryElement->getShippingOrderAddress();

                if (!is_null($shippingOrderAddress)) {
                    $deliveryAddress->addressLine1 = $shippingOrderAddress->getStreet();
                    $deliveryAddress->city = $shippingOrderAddress->getCity();
                    $deliveryAddress->company = $shippingOrderAddress->getCompany();
                    $deliveryAddress->firstname = $shippingOrderAddress->getFirstName();
                    $deliveryAddress->lastname = $shippingOrderAddress->getLastName();
                    $deliveryAddress->zipCode = $shippingOrderAddress->getZipcode();
                    $deliveryAddress->vatId = $shippingOrderAddress->getVatId();

                    $country = $shippingOrderAddress->getCountry();
                    if (!is_null($country) && !is_null($country->getIso())) {
                        $deliveryAddress->country = $country->getIso();
                    }

                    $countryState = $shippingOrderAddress->getCountryState();
                    if (!is_null($countryState)) {
                        $deliveryAddress->region = $countryState->getName();
                    }

                    $salutation = $shippingOrderAddress->getSalutation();
                    if (!is_null($salutation)) {
                        $deliveryAddress->salutation = $salutation->getDisplayName();
                    }
                }
            }
        }

        return $deliveryAddress;
    }
}
