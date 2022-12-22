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

        if ($deliveries) {
            $deliveryElements = $deliveries->getElements();

            if (is_array($deliveryElements) && !empty($deliveryElements)) {
                reset($deliveryElements);
                $deliveryElement = $deliveryElements[key($deliveryElements)];

                $shippingOrderAddress = $deliveryElement->getShippingOrderAddress();

                if ($shippingOrderAddress) {
                    $deliveryAddress->addressLine1 = $shippingOrderAddress->getStreet();
                    $deliveryAddress->city = $shippingOrderAddress->getCity();
                    $deliveryAddress->company = $shippingOrderAddress->getCompany();
                    $deliveryAddress->firstname = $shippingOrderAddress->getFirstName();
                    $deliveryAddress->lastname = $shippingOrderAddress->getLastName();
                    $deliveryAddress->zipCode = $shippingOrderAddress->getZipcode();
                    $deliveryAddress->vatId = $shippingOrderAddress->getVatId();

                    $country = $shippingOrderAddress->getCountry();
                    if ($country && $country->getIso()) {
                        $deliveryAddress->country = $country->getIso();
                    }

                    $countryState = $shippingOrderAddress->getCountryState();
                    if ($countryState) {
                        $deliveryAddress->region = $countryState->getName();
                    }

                    $salutation = $shippingOrderAddress->getSalutation();
                    if ($salutation) {
                        $deliveryAddress->salutation = $salutation->getDisplayName();
                    }
                }
            }
        }

        return $deliveryAddress;
    }
}
