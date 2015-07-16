<?php
namespace phtamas\yii2\mailer\tests\unit;

use phtamas\yii2\mailer\Mail;

class MailTest extends \PHPUnit_Framework_TestCase
{
    public function testBeginPlainTextBodyWithPlainTextMail()
    {
        $mail = new Mail('test-mail', $this->getMockBuilder('\Swift_Mailer')->disableOriginalConstructor()->getMock());
        $mail->isHtml = false;
        $this->setExpectedException('\BadMethodCallException');
        $mail->beginPlainTextBody();
    }

    public function testBeginPlainTextBody()
    {
        $view = $this->getMockBuilder('yii\base\View')
            ->disableOriginalConstructor()
            ->getMock();
        $view->expects($this->once())
            ->method('beginBlock');
        $mail = new Mail('test-mail', $this->getMockBuilder('\Swift_Mailer')->disableOriginalConstructor()->getMock());
        $mail->isHtml = true;
        $mail->setView($view);
        $mail->beginPlainTextBody();
    }

    public function testEndPlainTextBody()
    {
        $view = $this->getMockBuilder('yii\base\View')
            ->disableOriginalConstructor()
            ->getMock();
        $view->expects($this->once())
            ->method('endBlock');
        $mail = new Mail('test-mail', $this->getMockBuilder('\Swift_Mailer')->disableOriginalConstructor()->getMock());
        $mail->isHtml = true;
        $mail->setView($view);
        $mail->endPlainTextBody();
    }

    public function testSendPlainText()
    {
        $mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $mail = new Mail('plain-text', $mailer);
        $mail->setViewPath('@tests/templates');
        $mail->isHtml = false;
        $templateData = ['dynamicData' => 'dynamicValue'];
        $body = \Yii::$app->view->renderFile('@tests/templates/plain-text.php', $templateData, $mail);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($message) use ($body) {
                $this->assertInstanceOf('Swift_Message', $message);
                /* @var $message \Swift_Message */
                $this->assertEquals('text/plain', $message->getContentType());
                $this->assertEquals($body, $message->getBody());
                return true;
            }));
        $mail->send($templateData);
    }

    public function testHtmlWithImplicitPlainText()
    {
        $mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $mail = new Mail('html-without-plain-text', $mailer);
        $mail->setViewPath('@tests/templates');
        $mail->isHtml = true;
        $templateData = ['dynamicData' => 'dynamicValue'];
        $htmlBody = \Yii::$app->view->renderFile('@tests/templates/html-without-plain-text.php', $templateData, $mail);
        $plainTextBody = strip_tags($htmlBody);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($message) use ($htmlBody, $plainTextBody) {
                $this->assertInstanceOf('Swift_Message', $message);
                /* @var $message \Swift_Message */
                $this->assertEquals('multipart/alternative', $message->getContentType());
                $this->assertEquals($htmlBody, $message->getBody());
                $this->assertArrayHasKey(0, $message->getChildren());
                $this->assertEquals('text/plain', $message->getChildren()[0]->getContentType());
                $this->assertEquals($plainTextBody, $message->getChildren()[0]->getBody());
                return true;
            }));
        $mail->send($templateData);
    }

    public function testSendHtmlWithExplicitPlainText()
    {
        $mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $mail = new Mail('html-with-plain-text', $mailer);
        $mail->setViewPath('@tests/templates');
        $mail->isHtml = true;
        $templateData = ['dynamicData' => 'dynamicValue'];
        $htmlBody = \Yii::$app->view->renderFile('@tests/templates/html-with-plain-text.php', $templateData, $mail);
        $plainTextBody = \Yii::$app->view->blocks['mail.plainTextBody'];
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($message) use ($htmlBody, $plainTextBody) {
                $this->assertInstanceOf('Swift_Message', $message);
                /* @var $message \Swift_Message */
                $this->assertEquals('multipart/alternative', $message->getContentType());
                $this->assertEquals($htmlBody, $message->getBody());
                $this->assertArrayHasKey(0, $message->getChildren());
                $this->assertEquals('text/plain', $message->getChildren()[0]->getContentType());
                $this->assertEquals($plainTextBody, $message->getChildren()[0]->getBody());
                return true;
            }));
        $mail->send($templateData);
        $this->assertEmpty(\Yii::$app->view->blocks);
    }
}