<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1721401002AddPartialRefundPositions extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1721401002;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
ALTER TABLE `axytos_kaufaufrechnung_order_attributes`
    ADD COLUMN `partial_refund_positions` LONGTEXT NULL AFTER `partial_refund_last_reported_at`;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
