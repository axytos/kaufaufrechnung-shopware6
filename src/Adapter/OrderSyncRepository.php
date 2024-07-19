<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\OrderSyncRepositoryInterface;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\PluginOrderInterface;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class OrderSyncRepository implements OrderSyncRepositoryInterface
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository
     */
    private $orderEntityRepository;

    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Adapter\PluginOrderFactory
     */
    private $pluginOrderFactory;

    public function __construct(
        OrderEntityRepository $orderEntityRepository,
        PluginOrderFactory $pluginOrderFactory
    ) {
        $this->orderEntityRepository = $orderEntityRepository;
        $this->pluginOrderFactory = $pluginOrderFactory;
    }

    /**
     * @param string[] $orderStates
     * @param int|null $limit
     * @param string|null $startId
     * @return PluginOrderInterface[]
     */
    public function getOrdersByStates($orderStates, $limit = null, $startId = null)
    {
        $context = Context::createDefaultContext();
        $orderIds = $this->orderEntityRepository->getOrderIdsByStates($orderStates, $context, $limit, $startId);
        return $this->pluginOrderFactory->createMany($orderIds, $context);
    }

    /**
     * @param string|int $orderNumber
     * @return PluginOrderInterface|null
     */
    public function getOrderByOrderNumber($orderNumber)
    {
        $context = Context::createDefaultContext();
        $orderId = $this->orderEntityRepository->getOrderIdByOrderNumber(strval($orderNumber), $context);
        if (is_null($orderId)) {
            return null;
        }
        return $this->pluginOrderFactory->create($orderId, $context);
    }
}
