<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Order;

use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Shopware\Core\Framework\Context;
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

    public function cancelOrder(string $orderId, Context $context): void
    {
        $this->orderEntityRepository->cancelOrder($orderId, $context);
    }

    public function failPayment(string $orderId, Context $context): void
    {
        $this->orderEntityRepository->failPayment($orderId, $context);
    }

    public function payOrder(string $orderId, Context $context): void
    {
        $this->orderEntityRepository->payOrder($orderId, $context);
    }

    public function payOrderPartially(string $orderId, Context $context): void
    {
        $this->orderEntityRepository->payOrderPartially($orderId, $context);
    }

    public function setConfiguredAfterCheckoutOrderStatus(string $orderId, Context $context): void
    {
        $afterCheckoutOrderStatus = $this->pluginConfiguration->getAfterCheckoutOrderStatus();

        $this->orderEntityRepository->saveAfterCheckoutOrderStatus($orderId, $context, $afterCheckoutOrderStatus);
    }

    public function setConfiguredAfterCheckoutPaymentStatus(string $orderId, Context $context): void
    {
        $afterCheckoutPaymentStatus = $this->pluginConfiguration->getAfterCheckoutPaymentStatus();

        $this->orderEntityRepository->saveAfterCheckoutPaymentStatus($orderId, $context, $afterCheckoutPaymentStatus);
    }
}
