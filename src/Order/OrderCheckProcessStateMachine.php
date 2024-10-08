<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Order;

use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderCheckProcessStateMachine
{
    private const CUSTOM_FIELD_NAME = 'axytos_order_check_process_state';

    /**
     * @var OrderEntityRepository
     */
    private $orderEntityRepository;

    public function __construct(OrderEntityRepository $orderEntityRepository)
    {
        $this->orderEntityRepository = $orderEntityRepository;
    }

    public function getState(string $orderId, Context $context): string
    {
        $customFields = $this->orderEntityRepository->getCustomFields($orderId, $context);

        if (!array_key_exists(self::CUSTOM_FIELD_NAME, $customFields)) {
            return OrderCheckProcessStates::UNCHECKED;
        }

        return $customFields[self::CUSTOM_FIELD_NAME];
    }

    public function setUnchecked(string $orderId, SalesChannelContext $context): void
    {
        $this->updateState($orderId, OrderCheckProcessStates::UNCHECKED, $context);
    }

    public function setChecked(string $orderId, SalesChannelContext $context): void
    {
        $this->updateState($orderId, OrderCheckProcessStates::CHECKED, $context);
    }

    public function setConfirmed(string $orderId, SalesChannelContext $context): void
    {
        $this->updateState($orderId, OrderCheckProcessStates::CONFIRMED, $context);
    }

    public function setFailed(string $orderId, SalesChannelContext $context): void
    {
        $this->updateState($orderId, OrderCheckProcessStates::FAILED, $context);
    }

    private function updateState(string $orderId, string $orderCheckProcessState, SalesChannelContext $context): void
    {
        $customFields = [
            self::CUSTOM_FIELD_NAME => $orderCheckProcessState,
        ];

        $this->orderEntityRepository->updateCustomFields($orderId, $customFields, $context->getContext());
    }
}
