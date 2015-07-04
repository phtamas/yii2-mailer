<?php
namespace phtamas\yii2\mailer\tests\unit;

use phtamas\yii2\mailer\LogAdapter;
use yii\log\Logger;

class LogAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $logger = $this->getMockBuilder('yii\log\Logger')
            ->getMock();
        $logger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['message1', Logger::LEVEL_INFO, 'phtamas\yii2\mailer'],
                ['message2', Logger::LEVEL_INFO, 'phtamas\yii2\mailer']
            );
        $logAdapter = new LogAdapter($logger);
        $logAdapter->add('message1');
        $logAdapter->add('message2');
    }
}