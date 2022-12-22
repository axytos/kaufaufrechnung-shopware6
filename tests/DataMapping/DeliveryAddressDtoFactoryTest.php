<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\DataMapping;

use Axytos\KaufAufRechnung\Shopware\DataMapping\DeliveryAddressDtoFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Salutation\SalutationEntity;

class DeliveryAddressDtoFactoryTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataMapping\DeliveryAddressDtoFactory
     */
    private $sut;

    public function setUp(): void
    {
        $this->sut = new DeliveryAddressDtoFactory();
    }

    public function test_create_maps_deliveryAddress_correctly(): void
    {
        /** @var OrderEntity&MockObject $order */
        $order = $this->createMock(OrderEntity::class);
        /** @var OrderDeliveryCollection&MockObject $deliveries */
        $deliveries = $this->createMock(OrderDeliveryCollection::class);
        /** @var OrderDeliveryEntity&MockObject $deliveryElement */
        $deliveryElement = $this->createMock(OrderDeliveryEntity::class);
        $deliveryElements = [$deliveryElement];
        /** @var OrderAddressEntity&MockObject $shippingOrderAddress */
        $shippingOrderAddress = $this->createMock(OrderAddressEntity::class);
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
            ->method('getDeliveries')
            ->willReturn($deliveries);

        $deliveries
            ->method('getElements')
            ->willReturn($deliveryElements);

        $deliveryElement
            ->method('getShippingOrderAddress')
            ->willReturn($shippingOrderAddress);

        $shippingOrderAddress
            ->method('getStreet')
            ->willReturn($street);

        $shippingOrderAddress
            ->method('getCity')
            ->willReturn($city);

        $shippingOrderAddress
            ->method('getCompany')
            ->willReturn($company);

        $shippingOrderAddress
            ->method('getFirstName')
            ->willReturn($firstname);

        $shippingOrderAddress
            ->method('getLastName')
            ->willReturn($lastname);

        $shippingOrderAddress
            ->method('getZipcode')
            ->willReturn($zipCode);

        $shippingOrderAddress
            ->method('getVatId')
            ->willReturn($vatId);

        $shippingOrderAddress
            ->method('getCountry')
            ->willReturn($country);

        $country
            ->method('getIso')
            ->willReturn($countryIso);

        $shippingOrderAddress
            ->method('getCountryState')
            ->willReturn($countryState);

        $countryState
            ->method('getName')
            ->willReturn($stateName);

        $shippingOrderAddress
            ->method('getSalutation')
            ->willReturn($salutation);

        $salutation
            ->method('getDisplayName')
            ->willReturn($salutationDisplayName);

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
