<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Order;

use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderStateMachine
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository
     */
    private $orderEntityRepository;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration
     */
    private $pluginConfiguration;

    public function __construct(OrderEntityRepository $orderEntityRepository, PluginConfiguration $pluginConfiguration)
    {
        $this->orderEntityRepository = $orderEntityRepository;
        $this->pluginConfiguration = $pluginConfiguration;
    }

    public function cancelOrder(string $orderId, SalesChannelContext $salesChannelContext): void
    {
        $this->orderEntityRepository->cancelOrder($orderId, $salesChannelContext->getContext());
    }

    public function failPayment(string $orderId, SalesChannelContext $salesChannelContext): void
    {
        $this->orderEntityRepository->failPayment($orderId, $salesChannelContext->getContext());
    }

    public function payOrder(string $orderId, SalesChannelContext $salesChannelContext): void
    {
        $this->orderEntityRepository->payOrder($orderId, $salesChannelContext->getContext());
    }

    public function payOrderPartially(string $orderId, SalesChannelContext $salesChannelContext): void
    {
        $this->orderEntityRepository->payOrderPartially($orderId, $salesChannelContext->getContext());
    }

    public function setConfiguredAfterCheckoutOrderStatus(string $orderId, SalesChannelContext $salesChannelContext): void
    {
        $afterCheckoutOrderStatus = $this->pluginConfiguration->getAfterCheckoutOrderStatus();

        $this->orderEntityRepository->saveAfterCheckoutOrderStatus($orderId, $salesChannelContext->getContext(), $afterCheckoutOrderStatus);
    }

    public function setConfiguredAfterCheckoutPaymentStatus(string $orderId, SalesChannelContext $salesChannelContext): void
    {
        $afterCheckoutPaymentStatus = $this->pluginConfiguration->getAfterCheckoutPaymentStatus();

        $this->orderEntityRepository->saveAfterCheckoutPaymentStatus($orderId, $salesChannelContext->getContext(), $afterCheckoutPaymentStatus);
    }
}
