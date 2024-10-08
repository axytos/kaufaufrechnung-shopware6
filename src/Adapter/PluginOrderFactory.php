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
     * @var InvoiceOrderContextFactory
     */
    private $invoiceOrderContextFactory;
    /**
     * @var OrderEntityRepository
     */
    private $orderEntityRepository;
    /**
     * @var OrderStateMachine
     */
    private $orderStateMachine;
    /**
     * @var HashCalculator
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
     * @return PluginOrder
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
     *
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\PluginOrderInterface[]
     */
    public function createMany(array $orderIds, Context $context)
    {
        return array_map(function ($orderId) use ($context) {
            return $this->create($orderId, $context);
        }, $orderIds);
    }
}
