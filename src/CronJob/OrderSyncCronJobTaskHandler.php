<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\CronJob;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Core\OrderSyncWorker;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * reference: https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/add-scheduled-task.html#scheduledtask-and-its-handler
 *
 * @package Axytos\KaufAufRechnung\Shopware\CronJob
 */
#[AsMessageHandler(handles: OrderSyncCronJobTask::class)]
class OrderSyncCronJobTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler
     */
    private $errorHandler;
    /**
     * @var \Axytos\KaufAufRechnung\Core\OrderSyncWorker
     */
    private $orderSyncWorker;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\CronJob\OrderSyncCronJobScheduler
     */
    private $orderSyncCronJobScheduler;

    /**
     * NOTE: OrderSyncCronJobTaskHandler is EXPLICITLY wired in services.xml
     *
     * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator $pluginConfigurationValidator
     * @param \Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler $errorHandler
     * @param \Axytos\KaufAufRechnung\Core\OrderSyncWorker $orderSyncWorker
     * @return void
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        PluginConfigurationValidator $pluginConfigurationValidator,
        ErrorHandler $errorHandler,
        OrderSyncWorker $orderSyncWorker,
        OrderSyncCronJobScheduler $orderSyncCronJobScheduler
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
        $this->logger = $logger;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->errorHandler = $errorHandler;
        $this->orderSyncWorker = $orderSyncWorker;
        $this->orderSyncCronJobScheduler = $orderSyncCronJobScheduler;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        try {
            $this->logger->info('CronJob Order Sync started');

            if ($this->pluginConfigurationValidator->isInvalid()) {
                $this->logger->info('CronJob Order Sync aborted: invalid config');
                return;
            }

            $this->orderSyncWorker->sync();
            $this->logger->info('CronJob Order Sync succeeded');
        } catch (\Throwable $th) {
            $this->logger->error('CronJob Order Sync failed');
            $this->errorHandler->handle($th);
        } catch (\Exception $th) { // @phpstan-ignore-line because of php5 compatibility
            $this->logger->error('CronJob Order Sync failed');
            $this->errorHandler->handle($th);
        }
    }

    /**
     * @param \Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask $task
     * @param \Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity $taskEntity
     */
    protected function rescheduleTask(ScheduledTask $task, ScheduledTaskEntity $taskEntity): void
    {
        // check cron job scheduling configuration and prevent the task from being rescheduled
        // this can happen when cronjob is disabled but the task is still in the queue and tires to reschedule itself
        if ($this->orderSyncCronJobScheduler->isConfiguredToRunNever()) {
            $this->logger->info('CronJob Order Sync Rescheduling aborted: configured interval is never');
            $this->orderSyncCronJobScheduler->scheduleToRunNever($taskEntity, Context::createDefaultContext());
            return;
        }

        parent::rescheduleTask($task, $taskEntity);
    }
}
