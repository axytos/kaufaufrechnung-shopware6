<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

class OrderSyncLimit implements OrderSyncLimitInterface
{
    const KEY_LIMIT_ONE_MONTH = 'ORDER_SYNC_LIMIT_ONE_MONTH';
    const KEY_LIMIT_TWO_MONTH = 'ORDER_SYNC_LIMIT_TWO_MONTH';
    const KEY_LIMIT_THREE_MONTHS = 'ORDER_SYNC_LIMIT_THREE_MONTHS';
    const KEY_LIMIT_SIX_MONTHS = 'ORDER_SYNC_LIMIT_SIX_MONTHS';
    const KEY_LIMIT_ALL = 'ORDER_SYNC_LIMIT_ALL';

    /**
     * @var array<string, ?string>
     */
    private static $syncLimit = [
        self::KEY_LIMIT_ONE_MONTH => '-1 month', // must be greater than 0
        self::KEY_LIMIT_TWO_MONTH => '-2 months',
        self::KEY_LIMIT_THREE_MONTHS => '-3 months',
        self::KEY_LIMIT_SIX_MONTHS => '-6 months',
        self::KEY_LIMIT_ALL => null,
    ];

    /**
     * @var string
     */
    private $key;

    /**
     * @param string $key
     *
     * @return void
     */
    private function __construct($key)
    {
        $this->key = $key;
    }

    public static function create(string $key): OrderSyncLimit
    {
        return new OrderSyncLimit($key);
    }

    public function getOrderSyncCutoffDate(): ?string
    {
        $value = self::$syncLimit[$this->key] ?? null;

        if (null !== $value) {
            return (new \DateTimeImmutable($value))
                ->format(\Shopware\Core\Defaults::STORAGE_DATE_TIME_FORMAT)
            ;
        }

        return null;
    }
}
