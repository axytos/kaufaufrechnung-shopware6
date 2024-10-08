<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CompanyDto;
use Axytos\ECommerce\DataTransferObjects\CustomerDataDto;
use Shopware\Core\Checkout\Order\OrderEntity;

class CustomerDataDtoFactory
{
    public function create(OrderEntity $orderEntity): CustomerDataDto
    {
        $personalData = new CustomerDataDto();

        $orderCustomer = $orderEntity->getOrderCustomer();

        if (!is_null($orderCustomer)) {
            $personalData->email = $orderCustomer->getEmail();
            if (!is_null($orderCustomer->getCustomerNumber()) && !is_null($orderCustomer->getCustomerId())) {
                $personalData->externalCustomerId = $orderCustomer->getCustomerNumber() . '-' . $orderCustomer->getCustomerId();
            }

            if (!is_null($orderCustomer->getCompany())) {
                $personalData->company = new CompanyDto();

                $personalData->company->name = $orderCustomer->getCompany();
            }

            $customer = $orderCustomer->getCustomer();

            if (!is_null($customer)) {
                $birthDay = $customer->getBirthday();
                if (!is_null($birthDay)) {
                    $personalData->dateOfBirth = new \DateTimeImmutable('@' . $birthDay->getTimestamp(), $birthDay->getTimezone());
                }
            }
        }

        return $personalData;
    }
}
