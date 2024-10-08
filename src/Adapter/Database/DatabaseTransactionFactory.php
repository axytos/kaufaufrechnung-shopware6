<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Database;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Database\DatabaseTransactionFactoryInterface;
use Doctrine\DBAL\Connection;

class DatabaseTransactionFactory implements DatabaseTransactionFactoryInterface
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
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Database\DatabaseTransactionInterface
     */
    public function create()
    {
        return new DatabaseTransaction($this->connection);
    }
}
