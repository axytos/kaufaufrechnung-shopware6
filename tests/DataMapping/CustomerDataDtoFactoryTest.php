<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\DataMapping;

use Axytos\KaufAufRechnung\Shopware\DataMapping\CustomerDataDtoFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class CustomerDataDtoFactoryTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\CustomerDataDtoFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new CustomerDataDtoFactory();
    }

    public function test_create_maps_personalData_correctly_without_existing_customer(): void
    {
        /** @var OrderEntity&MockObject $order */
        $order = $this->createMock(OrderEntity::class);
        /** @var OrderCustomerEntity&MockObject $orderCustomer */
        $orderCustomer = $this->createMock(OrderCustomerEntity::class);

        $email = 'email';
        $customerNumber = 'customerNumber';
        $customerId = 'customerId';

        $order
            ->method('getOrderCustomer')
            ->willReturn($orderCustomer);

        $orderCustomer
            ->method('getEmail')
            ->willReturn($email);

        $orderCustomer
            ->method('getCustomerId')
            ->willReturn($customerId);

        $orderCustomer
            ->method('getCustomerNumber')
            ->willReturn($customerNumber);

        $orderCustomer
            ->method('getCustomer')
            ->willReturn(null);

        $actual = $this->sut->create($order);

        $this->assertSame($email, $actual->email);
        $this->assertSame($customerNumber . '-' . $customerId, $actual->externalCustomerId);
        $this->assertSame(null, $actual->dateOfBirth);
    }

    public function test_create_maps_personalData_correctly_for_existing_customer_without_birthdate(): void
    {
        /** @var OrderEntity&MockObject $order */
        $order = $this->createMock(OrderEntity::class);
        /** @var OrderCustomerEntity&MockObject $orderCustomer */
        $orderCustomer = $this->createMock(OrderCustomerEntity::class);
        /** @var CustomerEntity&MockObject $customer */
        $customer = $this->createMock(CustomerEntity::class);

        $email = 'email';
        $customerNumber = 'customerNumber';
        $customerId = 'customerId';

        $order
            ->method('getOrderCustomer')
            ->willReturn($orderCustomer);

        $orderCustomer
            ->method('getEmail')
            ->willReturn($email);

        $orderCustomer
            ->method('getCustomerId')
            ->willReturn($customerId);

        $orderCustomer
            ->method('getCustomerNumber')
            ->willReturn($customerNumber);

        $orderCustomer
            ->method('getCustomer')
            ->willReturn($customer);

        $customer
            ->method('getBirthDay')
            ->willReturn(null);

        $actual = $this->sut->create($order);

        $this->assertSame($email, $actual->email);
        $this->assertSame($customerNumber . '-' . $customerId, $actual->externalCustomerId);
        $this->assertSame(null, $actual->dateOfBirth);
    }

    public function test_create_maps_personalData_correctly_for_existing_customer_with_birthdate_date_time_type(): void
    {
        /** @var OrderEntity&MockObject $order */
        $order = $this->createMock(OrderEntity::class);
        /** @var OrderCustomerEntity&MockObject $orderCustomer */
        $orderCustomer = $this->createMock(OrderCustomerEntity::class);
        /** @var CustomerEntity&MockObject $customer */
        $customer = $this->createMock(CustomerEntity::class);
        /** @var \DateTime $dateTime */
        $dateTime = new \DateTime();

        $email = 'email';
        $customerNumber = 'customerNumber';
        $customerId = 'customerId';

        $order
            ->method('getOrderCustomer')
            ->willReturn($orderCustomer);

        $orderCustomer
            ->method('getEmail')
            ->willReturn($email);

        $orderCustomer
            ->method('getCustomerId')
            ->willReturn($customerId);

        $orderCustomer
            ->method('getCustomerNumber')
            ->willReturn($customerNumber);

        $orderCustomer
            ->method('getCustomer')
            ->willReturn($customer);

        $customer
            ->method('getBirthDay')
            ->willReturn($dateTime);

        $actual = $this->sut->create($order);

        $this->assertSame($email, $actual->email);
        $this->assertSame($customerNumber . '-' . $customerId, $actual->externalCustomerId);
        $this->assertNotNull($actual->dateOfBirth);
        $this->assertSame($dateTime->getTimestamp(), $actual->dateOfBirth->getTimestamp());
    }

    public function test_create_maps_personalData_correctly_for_existing_customer_with_birthdate_date_time_immutable_type(): void
    {
        /** @var OrderEntity&MockObject $order */
        $order = $this->createMock(OrderEntity::class);
        /** @var OrderCustomerEntity&MockObject $orderCustomer */
        $orderCustomer = $this->createMock(OrderCustomerEntity::class);
        /** @var CustomerEntity&MockObject $customer */
        $customer = $this->createMock(CustomerEntity::class);
        /** @var \DateTimeImmutable $dateTimeImmutable */
        $dateTimeImmutable = new \DateTimeImmutable();

        $email = 'email';
        $customerNumber = 'customerNumber';
        $customerId = 'customerId';
        $company = 'company';

        $order
            ->method('getOrderCustomer')
            ->willReturn($orderCustomer);

        $orderCustomer
            ->method('getEmail')
            ->willReturn($email);

        $orderCustomer
            ->method('getCompany')
            ->willReturn($company);

        $orderCustomer
            ->method('getCustomerId')
            ->willReturn($customerId);

        $orderCustomer
            ->method('getCustomerNumber')
            ->willReturn($customerNumber);

        $orderCustomer
            ->method('getCustomer')
            ->willReturn($customer);

        $customer
            ->method('getBirthDay')
            ->willReturn($dateTimeImmutable);

        $actual = $this->sut->create($order);

        $this->assertSame($email, $actual->email);
        /** @phpstan-ignore-next-line */
        $this->assertSame($company, $actual->company->name);
        $this->assertSame($customerNumber . '-' . $customerId, $actual->externalCustomerId);
        $this->assertNotNull($actual->dateOfBirth);
        $this->assertSame($dateTimeImmutable->getTimestamp(), $actual->dateOfBirth->getTimestamp());
    }
}
