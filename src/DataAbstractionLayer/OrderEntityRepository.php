<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer;

use Axytos\KaufAufRechnung\Shopware\Configuration\AfterCheckoutOrderStatus;
use Axytos\KaufAufRechnung\Shopware\Configuration\AfterCheckoutPaymentStatus;
use Axytos\KaufAufRechnung\Shopware\Core\AxytosInvoicePaymentHandler;
use Axytos\KaufAufRechnung\Shopware\Data\AxytosOrderAttributesEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class OrderEntityRepository
{
    /**
     * @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<OrderCollection>
     */
    private $orderRepository;
    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    /**
     * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        EntityRepository $orderRepository,
        StateMachineRegistry $stateMachineRegistry
    ) {
        $this->orderRepository = $orderRepository;
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    public function findOrder(string $orderId, Context $context): OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->getAssociation('stateMachineState'); // current state
        $criteria->addAssociation('transactions.stateMachineState'); // historical states?
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addAssociation('orderCustomer.customer');
        $criteria->addAssociation('orderCustomer.salutation');
        $criteria->addAssociation('transactions.paymentMethod.appPaymentMethod.app');
        $criteria->addAssociation('language');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('deliveries.shippingMethod');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('deliveries.shippingOrderAddress.countryState');
        $criteria->addAssociation('deliveries.shippingOrderAddress.salutation');
        $criteria->addAssociation('billingAddress.country');
        $criteria->addAssociation('billingAddress.countryState');
        $criteria->addAssociation('billingAddress.salutation');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAssociation('lineItems.promotion');
        $criteria->getAssociation('transactions');
        $criteria->getAssociation('documents');
        $criteria->getAssociation('documents.documentType');

        return $this->findFirst($criteria, $context);
    }

    public function getAxytosOrderAttributes(string $orderId, Context $context): AxytosOrderAttributesEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('axytosKaufAufRechnungOrderAttributes');

        /** @var OrderEntity */
        $orderEntity = $this->findFirst($criteria, $context);
        /** @var AxytosOrderAttributesEntity */
        $attributes = $orderEntity->getExtension('axytosKaufAufRechnungOrderAttributes');

        if (is_null($attributes)) {
            $attributes = new AxytosOrderAttributesEntity();
            $attributes->setId(Uuid::randomHex());
            $attributes->setShopwareOrderEntityId($orderEntity->getId());
            $attributes->setShopwareOrderNumber($orderEntity->getOrderNumber());
            $this->updateAxytosOrderAttributes($orderId, $attributes, $context);
        }

        return $attributes;
    }

    public function updateAxytosOrderAttributes(string $orderId, AxytosOrderAttributesEntity $axytosOrderAttributes, Context $context): void
    {
        /**
         * @var OrderEntity $orderEntity
         */
        $orderEntity = $this->orderRepository->search(new Criteria([$orderId]), $context)->first();

        $orderEntityId = $orderId;
        $orderEntityVersionId = is_null($orderEntity) ? null : $orderEntity->getVersionId();

        $orderData = [
            'id' => $orderId,
            'axytosKaufAufRechnungOrderAttributes' => [
                'id' => $axytosOrderAttributes->getId(),
                'shopwareOrderEntityId' => $orderEntityId,
                'shopwareOrderEntityVersionId' => $orderEntityVersionId,
                'shopwareOrderNumber' => $axytosOrderAttributes->getShopwareOrderNumber(),
                'orderPreCheckResult' => $axytosOrderAttributes->getOrderPreCheckResult(),
                'shippingReported' => $axytosOrderAttributes->getShippingReported(),
                'reportedTrackingCode' => $axytosOrderAttributes->getReportedTrackingCode(),
                'orderBasketHash' => $axytosOrderAttributes->getOrderBasketHash(),
                'orderState' => $axytosOrderAttributes->getOrderState(),
                'orderStateData' => $axytosOrderAttributes->getOrderStateData(),
            ],
        ];
        $this->orderRepository->upsert([$orderData], $context);
    }

    /**
     * @return array<mixed>
     */
    public function getCustomFields(string $orderId, Context $context): array
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('customFields');

        /** @var OrderEntity */
        $orderEntity = $this->findFirst($criteria, $context);

        if (is_null($orderEntity->getCustomFields())) {
            return [];
        }

        return $orderEntity->getCustomFields();
    }

    /**
     * @param array<mixed> $customFields
     */
    public function updateCustomFields(string $orderId, array $customFields, Context $context): void
    {
        $orderData = [
            'id' => $orderId,
            'customFields' => $customFields,
        ];
        $this->orderRepository->update([$orderData], $context);
    }

    public function cancelOrder(string $orderId, Context $context): void
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('deliveries');
        $criteria->setLimit(1);

        /** @var OrderEntity */
        $order = $this->orderRepository->search($criteria, $context)->first();

        $this->stateMachineRegistry->transition(new Transition(
            OrderDefinition::ENTITY_NAME,
            $order->getId(),
            StateMachineTransitionActions::ACTION_CANCEL,
            'stateId'
        ), $context);

        /** @var OrderTransactionCollection */
        $orderTransactions = $order->getTransactions();
        foreach ($orderTransactions as $orderTransaction) {
            $this->stateMachineRegistry->transition(new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $orderTransaction->getId(),
                StateMachineTransitionActions::ACTION_CANCEL,
                'stateId'
            ), $context);
        }

        /** @var OrderTransactionCollection */
        $orderDeliveries = $order->getDeliveries();
        foreach ($orderDeliveries as $orderDelivery) {
            $this->stateMachineRegistry->transition(new Transition(
                OrderDeliveryDefinition::ENTITY_NAME,
                $orderDelivery->getId(),
                StateMachineTransitionActions::ACTION_CANCEL,
                'stateId'
            ), $context);
        }
    }

    public function failPayment(string $orderId, Context $context): void
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->setLimit(1);

        /** @var OrderEntity */
        $order = $this->orderRepository->search($criteria, $context)->first();

        /** @var OrderTransactionCollection */
        $orderTransactions = $order->getTransactions();
        foreach ($orderTransactions as $orderTransaction) {
            $this->stateMachineRegistry->transition(new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $orderTransaction->getId(),
                StateMachineTransitionActions::ACTION_FAIL,
                'stateId'
            ), $context);
        }
    }

    private function findFirst(Criteria $criteria, Context $context): OrderEntity
    {
        /** @var EntityFinder<OrderEntity,OrderCollection> */
        $entityFinder = new EntityFinder($this->orderRepository);

        return $entityFinder->findFirst($criteria, $context);
    }

    public function payOrder(string $orderId, Context $context): void
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');

        $orderTransactionId = '';

        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, $context)->first();
        $orderTransactions = $orderEntity->getTransactions();
        if (!is_null($orderTransactions) && count($orderTransactions) > 0) {
            $orderTransaction = $orderTransactions->first();
            if (!is_null($orderTransaction)) {
                $orderTransactionId = $orderTransaction->getId();
            }
        }
        $this->stateMachineRegistry->transition(new Transition(
            OrderTransactionDefinition::ENTITY_NAME,
            $orderTransactionId,
            StateMachineTransitionActions::ACTION_PAID,
            'stateId'
        ), $context);
    }

    public function payOrderPartially(string $orderId, Context $context): void
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');

        $orderTransactionId = '';

        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, $context)->first();
        $orderTransactions = $orderEntity->getTransactions();
        if (!is_null($orderTransactions) && count($orderTransactions) > 0) {
            $orderTransaction = $orderTransactions->first();
            if (!is_null($orderTransaction)) {
                $orderTransactionId = $orderTransaction->getId();
            }
        }

        $this->stateMachineRegistry->transition(new Transition(
            OrderTransactionDefinition::ENTITY_NAME,
            $orderTransactionId,
            StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
            'stateId'
        ), $context);
    }

    public function saveAfterCheckoutOrderStatus(string $orderId, Context $context, AfterCheckoutOrderStatus $afterCheckoutOrderStatus): void
    {
        $this->stateMachineRegistry->transition(new Transition(
            OrderDefinition::ENTITY_NAME,
            $orderId,
            $afterCheckoutOrderStatus->getStateMachineTransactionAction(),
            'stateId'
        ), $context);
    }

    public function saveAfterCheckoutPaymentStatus(string $orderId, Context $context, AfterCheckoutPaymentStatus $afterCheckoutPaymentStatus): void
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->setLimit(1);

        /** @var OrderEntity */
        $order = $this->orderRepository->search($criteria, $context)->first();

        /** @var OrderTransactionCollection */
        $orderTransactions = $order->getTransactions();
        foreach ($orderTransactions as $orderTransaction) {
            $this->stateMachineRegistry->transition(new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $orderTransaction->getId(),
                $afterCheckoutPaymentStatus->getStateMachineTransactionAction(),
                'stateId'
            ), $context);
        }
    }

    /**
     * @param string[]    $orderStates
     * @param int|null    $limit
     * @param string|null $startId
     *
     * @return string[]
     */
    public function getOrderIdsByStates($orderStates, Context $context, $limit = null, $startId = null)
    {
        $criteria = new Criteria();
        $criteria
            ->addAssociation('transactions.paymentMethod')
            ->addFilter(new EqualsFilter('transactions.paymentMethod.handlerIdentifier', AxytosInvoicePaymentHandler::class))
        ;
        $criteria
            ->addAssociation('axytosKaufAufRechnungOrderAttributes')
            ->addFilter(new EqualsAnyFilter('axytosKaufAufRechnungOrderAttributes.orderState', $orderStates))
        ;

        $criteria->addSorting(new FieldSorting('orderNumber', FieldSorting::ASCENDING));

        if (!is_null($limit)) {
            $criteria->setLimit($limit);
        }

        if (!is_null($startId)) {
            $criteria->addFilter(new RangeFilter('orderNumber', [
                RangeFilter::GTE => $startId,
            ]));
        }

        $orderIds = $this->orderRepository->searchIds($criteria, $context)->getIds();

        /** @var string[] */
        return array_values($orderIds);
    }

    /**
     * @param string $orderNumber
     *
     * @return string|null
     */
    public function getOrderIdByOrderNumber($orderNumber, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));

        return $this->orderRepository->searchIds($criteria, $context)->firstId();
    }
}
