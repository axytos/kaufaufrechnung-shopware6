<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Database;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Database\DatabaseTransactionInterface;
use Doctrine\DBAL\Connection;

class DatabaseTransaction implements DatabaseTransactionInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return void
     */
    public function begin()
    {
        $this->connection->beginTransaction();
    }

    /**
     * @return void
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * @return void
     */
    public function rollback()
    {
        $this->connection->rollBack();
    }
}
