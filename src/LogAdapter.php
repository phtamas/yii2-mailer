<?php
namespace phtamas\yii2\mailer;

use yii\log\Logger;

class LogAdapter implements \Swift_Plugins_Logger
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Add a log entry.
     *
     * @param string $entry
     */
    public function add($entry)
    {
        $this->logger->log($entry, Logger::LEVEL_INFO, __NAMESPACE__);
    }

    /**
     * Not implemented
     */
    public function clear()
    {

    }

    /**
     * Not implemented
     */
    public function dump()
    {

    }
}