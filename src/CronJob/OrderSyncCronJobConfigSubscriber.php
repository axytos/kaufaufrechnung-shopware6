<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\CronJob;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfigurationValueNames;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * reference: https://stackoverflow.com/a/75177047
 *
 * @package Axytos\KaufAufRechnung\Shopware\CronJob
 */
class OrderSyncCronJobConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface
     */
    private $logger;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\CronJob\OrderSyncCronJobScheduler
     */
    private $orderSyncCronJobScheduler;

    /**
     * @param \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface $logger
     * @return void
     */
    public function __construct(
        LoggerAdapterInterface $logger,
        OrderSyncCronJobScheduler $orderSyncCronJobScheduler
    ) {
        $this->logger = $logger;
        $this->orderSyncCronJobScheduler = $orderSyncCronJobScheduler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onSystemConfigChanged'
        ];
    }

    public function onSystemConfigChanged(SystemConfigChangedEvent $event): void
    {
        try {
            if ($event->getKey() !== PluginConfigurationValueNames::ORDER_SYNC_CRONJOB_INTERVAL) {
                return;
            }
            $this->orderSyncCronJobScheduler->scheduleOrderSyncTask();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
