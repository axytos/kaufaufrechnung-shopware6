<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

use Axytos\KaufAufRechnung\Shopware\CronJob\OrderSyncCronJobIntervalInterface;
use DateTimeImmutable;
use DateTimeInterface;

class OrderSyncCronJobInterval implements OrderSyncCronJobIntervalInterface
{
    const KEY_ONCE_NEVER = 'ORDER_SYNC_CRONJOB_INTERVAL_NEVER';
    const KEY_ONCE_EVERY_10_SECONDS = 'ORDER_SYNC_CRONJOB_INTERVAL_ONCE_EVERY_10_SECONDS';
    const KEY_ONCE_EVERY_24_HOURS_AT_MIDNIGHT = 'ORDER_SYNC_CRONJOB_INTERVAL_ONCE_EVERY_24_HOURS_AT_MIDNIGHT';

    /**
     * @var array<string, int>
     */
    private static $runIntervalSeconds = [
        self::KEY_ONCE_NEVER => 1, // must be greater than 0
        self::KEY_ONCE_EVERY_10_SECONDS => 10,
        self::KEY_ONCE_EVERY_24_HOURS_AT_MIDNIGHT   => 24 * 60 * 60,
    ];

    public function isNever(): bool
    {
        return $this->key === self::KEY_ONCE_NEVER;
    }

    /**
     * @return string
     */
    public static function getDefaultKey(): string
    {
        return self::KEY_ONCE_NEVER;
    }

    /**
     * @return int
     */
    public static function getDefaultRunIntervalSeconds(): int
    {
        return self::$runIntervalSeconds[self::getDefaultKey()];
    }

    /**
     * @param string $key
     * @return OrderSyncCronJobInterval
     */
    public static function create(string $key): OrderSyncCronJobInterval
    {
        return new OrderSyncCronJobInterval($key);
    }

    /**
     * @var string
     */
    private $key;

    /**
     * @param string $key
     * @return void
     */
    private function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @return int
     */
    public function getRunIntervalSeconds(): int
    {
        if (array_key_exists($this->key, self::$runIntervalSeconds)) {
            return self::$runIntervalSeconds[$this->key];
        }
        return self::getDefaultRunIntervalSeconds();
    }

    /**
     * @return DateTimeInterface
     */
    public function getNextExecutionTime(): DateTimeInterface
    {
        switch ($this->key) {
            case self::KEY_ONCE_NEVER:
                return self::getNextExecutionTimeYesterday();
            case self::KEY_ONCE_EVERY_10_SECONDS:
                return self::getNextExecutionTimeFromNow($this->getRunIntervalSeconds());
            case self::KEY_ONCE_EVERY_24_HOURS_AT_MIDNIGHT:
                return self::getNextExecutionTimeTomorrowAtMidnight();
            default:
                return self::getNextExecutionTimeYesterday();
        }
    }

    /**
     * @return DateTimeInterface
     */
    private static function getNextExecutionTimeYesterday(): DateTimeInterface
    {
        return (new DateTimeImmutable('yesterday midnight'));
    }

    /**
     * @return DateTimeInterface
     */
    private static function getNextExecutionTimeTomorrowAtMidnight(): DateTimeInterface
    {
        return (new DateTimeImmutable('tomorrow midnight'));
    }

    /**
     * @param int $seconds
     * @return DateTimeInterface
     */
    private static function getNextExecutionTimeFromNow($seconds): DateTimeInterface
    {
        return (new DateTimeImmutable('now'))->add(new \DateInterval('PT' . $seconds . 'S'));
    }
}
