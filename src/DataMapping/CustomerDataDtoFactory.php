<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CompanyDto;
use Axytos\ECommerce\DataTransferObjects\CustomerDataDto;
use DateTimeImmutable;
use Shopware\Core\Checkout\Order\OrderEntity;

class CustomerDataDtoFactory
{
    public function create(OrderEntity $orderEntity): CustomerDataDto
    {
        $personalData = new CustomerDataDto();

        $orderCustomer = $orderEntity->getOrderCustomer();

        if ($orderCustomer) {
            $personalData->email = $orderCustomer->getEmail();
            if ($orderCustomer->getCustomerNumber() && $orderCustomer->getCustomerId()) {
                $personalData->externalCustomerId = $orderCustomer->getCustomerNumber() . '-' . $orderCustomer->getCustomerId();
            }


            if ($orderCustomer->getCompany()) {
                $personalData->company = new CompanyDto();

                $personalData->company->name = $orderCustomer->getCompany();
            }

            $customer = $orderCustomer->getCustomer();

            if ($customer) {
                $birthDay = $customer->getBirthday();
                if ($birthDay) {
                    $personalData->dateOfBirth = new DateTimeImmutable('@' . $birthDay->getTimestamp(), $birthDay->getTimezone());
                }
            }
        }

        return $personalData;
    }
}
