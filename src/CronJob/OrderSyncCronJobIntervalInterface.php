<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\CronJob;

interface OrderSyncCronJobIntervalInterface
{
    public function isNever(): bool;
    public function getRunIntervalSeconds(): int;
    public function getNextExecutionTime(): \DateTimeInterface;
}
