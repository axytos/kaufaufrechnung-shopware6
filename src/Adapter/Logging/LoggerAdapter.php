<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\Logging;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface;
use Axytos\KaufAufRechnung\Shopware\Logging\LoggerAdapter as PluginLogger;

class LoggerAdapter implements LoggerAdapterInterface
{
    /**
     * @var PluginLogger
     */
    private $logger;

    public function __construct(PluginLogger $logger)
    {
        $this->logger = $logger;
    }

    public function error($message)
    {
        $this->logger->error($message);
    }

    public function warning($message)
    {
        $this->logger->warning($message);
    }

    public function info($message)
    {
        $this->logger->info($message);
    }

    public function debug($message)
    {
        $this->logger->debug($message);
    }
}
