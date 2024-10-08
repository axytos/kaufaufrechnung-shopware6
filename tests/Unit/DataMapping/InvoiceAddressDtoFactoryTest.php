<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung\Shopware\DataMapping\InvoiceAddressDtoFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Salutation\SalutationEntity;

/**
 * @internal
 */
class InvoiceAddressDtoFactoryTest extends TestCase
{
    /**
     * @var InvoiceAddressDtoFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new InvoiceAddressDtoFactory();
    }

    public function test_create_maps_invoice_correctly(): void
    {
        /** @var OrderEntity&MockObject $order */
        $order = $this->createMock(OrderEntity::class);
        /** @var OrderAddressEntity&MockObject $billingAddress */
        $billingAddress = $this->createMock(OrderAddressEntity::class);
        /** @var CountryEntity&MockObject $country */
        $country = $this->createMock(CountryEntity::class);
        /** @var CountryStateEntity&MockObject $countryState */
        $countryState = $this->createMock(CountryStateEntity::class);
        /** @var SalutationEntity&MockObject $salutation */
        $salutation = $this->createMock(SalutationEntity::class);

        $street = 'street';
        $city = 'city';
        $company = 'company';
        $firstname = 'firstname';
        $lastname = 'lastname';
        $zipCode = 'zipCode';
        $vatId = 'vatId';
        $countryIso = 'countryIso';
        $stateName = 'stateName';
        $salutationDisplayName = 'salutationDisplayName';

        $order
            ->method('getBillingAddress')
            ->willReturn($billingAddress)
        ;

        $billingAddress
            ->method('getStreet')
            ->willReturn($street)
        ;

        $billingAddress
            ->method('getCity')
            ->willReturn($city)
        ;

        $billingAddress
            ->method('getCompany')
            ->willReturn($company)
        ;

        $billingAddress
            ->method('getFirstName')
            ->willReturn($firstname)
        ;

        $billingAddress
            ->method('getLastName')
            ->willReturn($lastname)
        ;

        $billingAddress
            ->method('getZipcode')
            ->willReturn($zipCode)
        ;

        $billingAddress
            ->method('getVatId')
            ->willReturn($vatId)
        ;

        $billingAddress
            ->method('getCountry')
            ->willReturn($country)
        ;

        $country
            ->method('getIso')
            ->willReturn($countryIso)
        ;

        $billingAddress
            ->method('getCountryState')
            ->willReturn($countryState)
        ;

        $countryState
            ->method('getName')
            ->willReturn($stateName)
        ;

        $billingAddress
            ->method('getSalutation')
            ->willReturn($salutation)
        ;

        $salutation
            ->method('getDisplayName')
            ->willReturn($salutationDisplayName)
        ;

        $actual = $this->sut->create($order);

        $this->assertSame($street, $actual->addressLine1);
        $this->assertSame($city, $actual->city);
        $this->assertSame($company, $actual->company);
        $this->assertSame($firstname, $actual->firstname);
        $this->assertSame($lastname, $actual->lastname);
        $this->assertSame($zipCode, $actual->zipCode);
        $this->assertSame($vatId, $actual->vatId);
        $this->assertSame($countryIso, $actual->country);
        $this->assertSame($stateName, $actual->region);
        $this->assertSame($salutationDisplayName, $actual->salutation);
    }
}
