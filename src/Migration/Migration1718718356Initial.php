<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * create migration:
 * 1. run  bin/console database:create-migration -p AxytosKaufAufRechnung --name <MigrationName>   to generate new migration class
 * 2. run  bin/console dal:create:schema                                                           to generate sql create statements
 *
 * commands are mapped to composer scripts:
 * - composer shopware-create-migration
 * - composer shopware-create-schema
 *
 * execute migration:
 * - plugin needs to be updated or re-installed
 * - or run bin/console database:migrate
 *
 * see references:
 * - https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/database-migrations.html#create-migration
 * - https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/database-migrations.html#sql-schema
 *
 * @package Axytos\KaufAufRechnung\Shopware\Migration
 */
class Migration1718718356Initial extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1718718356;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL

CREATE TABLE IF NOT EXISTS `axytos_kaufaufrechnung_order_attributes` (
    `id` BINARY(16) NOT NULL,
    `shopware_order_entity_id` BINARY(16) NULL,
    `shopware_order_entity_version_id` BINARY(16) NULL,
    `shopware_order_number` VARCHAR(255) NULL,
    `order_pre_check_result` JSON NULL,
    `shipping_reported` TINYINT(1) NULL DEFAULT 0,
    `reported_tracking_code` LONGTEXT NULL,
    `order_basket_hash` LONGTEXT NULL,
    `order_state` VARCHAR(255) NULL,
    `order_state_data` LONGTEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk.axytos_kaufaufrechnung_order_attributes.order_id` 
        FOREIGN KEY (`shopware_order_entity_id`, `shopware_order_entity_version_id`) 
        REFERENCES `order` (`id`, `version_id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
);

SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
