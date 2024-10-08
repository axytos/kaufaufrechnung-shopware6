<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\CronJob;

use Axytos\KaufAufRechnung\Shopware\Configuration\OrderSyncCronJobInterval;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * reference: https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/add-scheduled-task.html#scheduledtask-and-its-handler.
 */
class OrderSyncCronJobTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'asytos.kaufaufrechnung.order_sync_cronjob';
    }

    public static function getDefaultInterval(): int
    {
        return OrderSyncCronJobInterval::getDefaultRunIntervalSeconds();
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }
}
