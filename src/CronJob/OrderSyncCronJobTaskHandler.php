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
 * reference: https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/add-scheduled-task.html#scheduledtask-and-its-handler.
 */
#[AsMessageHandler(handles: OrderSyncCronJobTask::class)]
class OrderSyncCronJobTaskHandler extends ScheduledTaskHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var ErrorHandler
     */
    private $errorHandler;
    /**
     * @var OrderSyncWorker
     */
    private $orderSyncWorker;
    /**
     * @var OrderSyncCronJobScheduler
     */
    private $orderSyncCronJobScheduler;

    /**
     * NOTE: OrderSyncCronJobTaskHandler is EXPLICITLY wired in services.xml.
     *
     * @param \Shopware\Core\Framework\DataAbstractionLayer\EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     *
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
