<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

interface OrderSyncLimitInterface
{
    public function getOrderSyncCutoffDate(): ?string;
}
