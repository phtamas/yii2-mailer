<?php
namespace phtamas\yii2\mailer\tests\unit;

use phtamas\yii2\mailer\Mail;

class MailTest extends \PHPUnit_Framework_TestCase
{
    public function testSend()
    {
        $templateData = ['key' => 'value'];
        $view = $this->getMockBuilder('yii\web\View')
            ->disableOriginalConstructor()
            ->getMock();
        $view->method('render')
            ->will($this->onConsecutiveCalls('plain text body', 'html body'));
        $view->expects($this->exactly(2))
            ->method('render')
            ->withConsecutive(
                [$this->equalTo('plain-text/test-mail'), $this->equalTo($templateData)],
                [$this->equalTo('html/test-mail'), $this->equalTo($templateData)]
            );

        $mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($message) {
                $this->assertInstanceOf('Swift_Message', $message);
                /* @var $message \Swift_Message */
                $this->assertEquals('multipart/alternative', $message->getContentType());
                $this->assertEquals('html body', $message->getBody());
                $this->assertArrayHasKey(0, $message->getChildren());
                $this->assertEquals('text/plain', $message->getChildren()[0]->getContentType());
                $this->assertEquals('plain text body', $message->getChildren()[0]->getBody());
                return true;
            }));

        $mail = new Mail('test-mail', $mailer);
        $mail->setView($view);
        $mail->send($templateData);
    }
}