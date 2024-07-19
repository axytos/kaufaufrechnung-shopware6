<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter;

use Axytos\KaufAufRechnung\Shopware\Adapter\HashCalculation\HashCalculator;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use Shopware\Core\Framework\Context;

class PluginOrderFactory
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory
     */
    private $invoiceOrderContextFactory;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\OrderEntityRepository
     */
    private $orderEntityRepository;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine
     */
    private $orderStateMachine;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Adapter\HashCalculation\HashCalculator
     */
    private $hashCalculator;

    public function __construct(
        InvoiceOrderContextFactory $invoiceOrderContextFactory,
        OrderEntityRepository $orderEntityRepository,
        OrderStateMachine $orderStateMachine,
        HashCalculator $hashCalculator
    ) {
        $this->invoiceOrderContextFactory = $invoiceOrderContextFactory;
        $this->orderEntityRepository = $orderEntityRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->hashCalculator = $hashCalculator;
    }

    /**
     * @param string $orderId
     * @param \Shopware\Core\Framework\Context $context
     * @return \Axytos\KaufAufRechnung\Shopware\Adapter\PluginOrder
     */
    public function create(string $orderId, Context $context)
    {
        $invoiceOrderContext = $this->invoiceOrderContextFactory->getInvoiceOrderContext($orderId, $context);
        return new PluginOrder(
            $invoiceOrderContext,
            $this->orderEntityRepository,
            $this->orderStateMachine,
            $this->hashCalculator
        );
    }

    /**
     * @param string[] $orderIds
     * @param \Shopware\Core\Framework\Context $context
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\PluginOrderInterface[]
     */
    public function createMany(array $orderIds, Context $context)
    {
        return array_map(function ($orderId) use ($context) {
            return $this->create($orderId, $context);
        }, $orderIds);
    }
}
