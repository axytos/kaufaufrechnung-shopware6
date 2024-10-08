<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Order;

use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\KaufAufRechnung\Shopware\Order\OrderCheckProcessStateMachine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
class OrderCheckProcessStateMachineTest extends TestCase
{
    private const ORDER_ID = 'orderId';
    private const CUSTOM_FIELD_NAME = 'axytos_order_check_process_state';

    /** @var OrderEntityRepository&MockObject */
    private $orderEntityRepository;

    /**
     * @var OrderCheckProcessStateMachine
     */
    private $sut;

    /** @var SalesChannelContext&MockObject */
    private $salesChannelContext;

    /** @var Context&MockObject */
    private $context;

    public function setUp(): void
    {
        $this->orderEntityRepository = $this->createMock(OrderEntityRepository::class);

        $this->sut = new OrderCheckProcessStateMachine($this->orderEntityRepository);

        $this->salesChannelContext = $this->createMock(SalesChannelContext::class);
        $this->context = $this->createMock(Context::class);

        $this->setUpSalesChannelContext();
    }

    private function setUpSalesChannelContext(): void
    {
        $this->salesChannelContext
            ->method('getContext')
            ->willReturn($this->context)
        ;
    }

    /**
     * @param array<string,string> $customFields
     */
    private function setUpCustomFields(array $customFields): void
    {
        $this->orderEntityRepository
            ->method('getCustomFields')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($customFields)
        ;
    }

    /**
     * @param array<string,string> $expectedCustomFields
     */
    private function expectCustomFieldsUpdate(array $expectedCustomFields): void
    {
        $this->orderEntityRepository
            ->expects($this->once())
            ->method('updateCustomFields')
            ->with(self::ORDER_ID, $expectedCustomFields, $this->context)
        ;
    }

    public function test_get_state_returns_unchecke_d_as_default(): void
    {
        $this->setUpCustomFields([]);

        $actual = $this->sut->getState(self::ORDER_ID, $this->context);

        $this->assertEquals(OrderCheckProcessStates::UNCHECKED, $actual);
    }

    /**
     * @dataProvider dataProvider_test_getState
     */
    #[DataProvider('dataProvider_test_getState')]
    public function test_get_state(string $state): void
    {
        $this->setUpCustomFields([
            self::CUSTOM_FIELD_NAME => $state,
        ]);

        $actual = $this->sut->getState(self::ORDER_ID, $this->context);

        $this->assertEquals($state, $actual);
    }

    /**
     * @return array<array<string>>
     */
    public static function dataProvider_test_getState(): array
    {
        return [
            [OrderCheckProcessStates::UNCHECKED],
            [OrderCheckProcessStates::CHECKED],
            [OrderCheckProcessStates::CONFIRMED],
            [OrderCheckProcessStates::FAILED],
        ];
    }

    public function test_set_unchecked(): void
    {
        $this->expectCustomFieldsUpdate([
            self::CUSTOM_FIELD_NAME => OrderCheckProcessStates::UNCHECKED,
        ]);

        $this->sut->setUnchecked(self::ORDER_ID, $this->salesChannelContext);
    }

    public function test_set_checked(): void
    {
        $this->expectCustomFieldsUpdate([
            self::CUSTOM_FIELD_NAME => OrderCheckProcessStates::CHECKED,
        ]);

        $this->sut->setChecked(self::ORDER_ID, $this->salesChannelContext);
    }

    public function test_set_confirmed(): void
    {
        $this->expectCustomFieldsUpdate([
            self::CUSTOM_FIELD_NAME => OrderCheckProcessStates::CONFIRMED,
        ]);

        $this->sut->setConfirmed(self::ORDER_ID, $this->salesChannelContext);
    }

    public function test_set_failed(): void
    {
        $this->expectCustomFieldsUpdate([
            self::CUSTOM_FIELD_NAME => OrderCheckProcessStates::FAILED,
        ]);

        $this->sut->setFailed(self::ORDER_ID, $this->salesChannelContext);
    }
}
