<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Information\Checkout;

use Axytos\ECommerce\DataTransferObjects\CustomerDataDto;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\Checkout\CustomerInterface;

class Customer implements CustomerInterface
{
    /**
     * @var CustomerDataDto
     */
    private $dto;

    public function __construct(CustomerDataDto $dto)
    {
        $this->dto = $dto;
    }

    public function getCustomerNumber()
    {
        return $this->dto->externalCustomerId;
    }

    public function getDateOfBirth()
    {
        return $this->dto->dateOfBirth;
    }

    public function getEmailAddress()
    {
        return $this->dto->email;
    }

    public function getCompanyName()
    {
        $company = $this->dto->company;
        $name = !is_null($company) ? $company->name : null;

        return strval($name);
    }
}
