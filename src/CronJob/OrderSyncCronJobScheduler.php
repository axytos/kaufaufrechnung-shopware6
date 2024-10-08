<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\CronJob;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;

class OrderSyncCronJobScheduler
{
    /**
     * @var PluginConfiguration
     */
    private $pluginConfiguration;

    /**
     * @var \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<ScheduledTaskCollection>
     */
    private $scheduledTaskRepository;

    /**
     * @var LoggerAdapterInterface
     */
    private $logger;

    /**
     * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     *
     * @return void
     */
    public function __construct(
        PluginConfiguration $pluginConfiguration,
        EntityRepository $scheduledTaskRepository,
        LoggerAdapterInterface $logger
    ) {
        $this->pluginConfiguration = $pluginConfiguration;
        $this->scheduledTaskRepository = $scheduledTaskRepository;
        $this->logger = $logger;
    }

    public function getConfiguredOrderSyncCronJobInterval(): OrderSyncCronJobIntervalInterface
    {
        return $this->pluginConfiguration->getOrderSyncCronJobInterval();
    }

    public function isConfiguredToRunNever(): bool
    {
        return $this->getConfiguredOrderSyncCronJobInterval()->isNever();
    }

    public function scheduleOrderSyncTask(?OrderSyncCronJobIntervalInterface $interval = null, ?ScheduledTaskEntity $task = null, ?Context $context = null): void
    {
        if (is_null($interval)) {
            $interval = $this->getConfiguredOrderSyncCronJobInterval();
        }

        if (is_null($context)) {
            $context = Context::createDefaultContext();
        }

        if (is_null($task)) {
            $task = $this->findScheduledTaskEntity($context);
            if (is_null($task)) {
                // can happen during plugin uninstallation
                $this->logger->warning('Task ' . OrderSyncCronJobTask::getTaskName() . ' not found.');

                return;
            }
        }

        if ($interval->isNever()) {
            $this->scheduleToRunNever($task, $context);
        } else {
            $runInterval = $interval->getRunIntervalSeconds();
            $nextExecutionTime = $interval->getNextExecutionTime();
            $this->scheduledTaskRepository->update([
                [
                    'id' => $task->getId(),
                    'status' => ScheduledTaskDefinition::STATUS_SCHEDULED,
                    'runInterval' => $runInterval,
                    'nextExecutionTime' => $nextExecutionTime,
                ],
            ], $context);
            $this->logger->info("OrderSyncCronJobTask scheduled to run once every {$runInterval} seconds. Next at " . $nextExecutionTime->format(\DateTimeImmutable::ATOM));
        }
    }

    public function scheduleToRunNever(?ScheduledTaskEntity $task = null, ?Context $context = null): void
    {
        if (is_null($context)) {
            $context = Context::createDefaultContext();
        }

        if (is_null($task)) {
            $task = $this->findScheduledTaskEntity($context);
            if (is_null($task)) {
                // can happen during plugin uninstallation
                $this->logger->warning('Task ' . OrderSyncCronJobTask::getTaskName() . ' not found.');

                return;
            }
        }

        $lastExecutionTime = $task->getLastExecutionTime();

        // check if the task has never run at all, e.g. after first installation
        if (is_null($lastExecutionTime)) {
            $lastExecutionTime = new \DateTimeImmutable('now');
        }

        $this->scheduledTaskRepository->update([
            [
                'id' => $task->getId(),
                'status' => ScheduledTaskDefinition::STATUS_INACTIVE,
                'nextExecutionTime' => $lastExecutionTime,
            ],
        ], $context);
        $this->logger->info('OrderSyncCronJobTask disabled.');
    }

    /**
     * @return ScheduledTaskEntity|null
     */
    private function findScheduledTaskEntity(?Context $context = null)
    {
        if (is_null($context)) {
            $context = Context::createDefaultContext();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', OrderSyncCronJobTask::getTaskName()));

        /** @var ScheduledTaskEntity|null */
        return $this->scheduledTaskRepository->search($criteria, $context)->first();
    }
}
