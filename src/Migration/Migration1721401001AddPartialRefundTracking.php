<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1721401001AddPartialRefundTracking extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1721401001;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
ALTER TABLE `axytos_kaufaufrechnung_order_attributes`
    ADD COLUMN `refund_reported` TINYINT(1) NULL DEFAULT 0 AFTER `shipping_reported`,
    ADD COLUMN `partial_refund_last_reported_at` DATETIME(3) NULL AFTER `refund_reported`;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
