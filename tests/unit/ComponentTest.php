<?php
namespace phtamas\yii2\mailer\tests;

use phtamas\yii2\mailer\Component;
use phtamas\yii2\mailer\Mail;

class ComponentTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMailerWithUnconfiguredSmtpTransport()
    {
        $component = new Component([
            'transports' => ['smtp'],
        ]);
        $this->assertInstanceOf('Swift_Mailer', $component->getMailer());
        $this->assertInstanceOf('Swift_SmtpTransport', $component->getMailer()->getTransport());
    }

    public function testGetMailerWithFullyConfiguredSmtpTransport()
    {
        $component = new Component([
            'transports' => [
                [
                    'type' => 'smtp',
                    'host' => 'host',
                    'port' => 999,
                    'encryption' => 'ssl',
                    'username' => 'username',
                    'password' => 'password',
                ]
            ],
        ]);
        $this->assertInstanceOf('Swift_Mailer', $component->getMailer());
        $transport = $component->getMailer()->getTransport();
        $this->assertInstanceOf('Swift_SmtpTransport', $transport);
        /* @var $transport \Swift_SmtpTransport */
        $this->assertEquals('host', $transport->getHost());
        $this->assertEquals(999, $transport->getPort());
        $this->assertEquals('ssl', $transport->getEncryption());
        $this->assertEquals('username', $transport->getUsername());
        $this->assertEquals('password', $transport->getPassword());
    }

    public function testGetMailerWithUnconfiguredMailTransport()
    {
        $component = new Component([
            'transports' => ['mail'],
        ]);
        $this->assertInstanceOf('Swift_Mailer', $component->getMailer());
        $this->assertInstanceOf('Swift_MailTransport', $component->getMailer()->getTransport());
    }

    public function testGetMailerWithFullyConfiguredMailTrasport()
    {
        $component = new Component([
            'transports' => [
                [
                    'type' => 'mail',
                    'extraParams' => 'params'
                ]
            ],
        ]);
        $this->assertInstanceOf('Swift_Mailer', $component->getMailer());
        $transport = $component->getMailer()->getTransport();
        $this->assertInstanceOf('Swift_MailTransport', $transport);
        /* @var $transport \Swift_MailTransport */
        $this->assertEquals('params', $transport->getExtraParams());
    }

    public function testGetMailerWithNullTransport()
    {
        $component = new Component([
            'transports' => ['null'],
        ]);
        $this->assertInstanceOf('Swift_Mailer', $component->getMailer());
        $transport = $component->getMailer()->getTransport();
        $this->assertInstanceOf('Swift_NullTransport', $transport);
    }

    public function testGetMailerWithFailoverTransport()
    {
        $component = new Component([
            'transports' => ['smtp', 'mail'],
        ]);
        $this->assertInstanceOf('Swift_Mailer', $component->getMailer());
        $transport = $component->getMailer()->getTransport();
        $this->assertInstanceOf('Swift_FailoverTransport', $transport);
        /* @var $transport \Swift_FailoverTransport */
        $transports = $transport->getTransports();
        $this->assertArrayHasKey(0, $transports);
        $this->assertInstanceOf('Swift_SmtpTransport', $transports[0]);
        $this->assertArrayHasKey(1, $transports);
        $this->assertInstanceOf('Swift_MailTransport', $transports[1]);
    }

    public function testGetMailerWithTransportInstance()
    {
        $component = new Component([
            'transports' => [\Swift_NullTransport::newInstance()],
        ]);
        $this->assertInstanceOf('Swift_Mailer', $component->getMailer());
    }

    public function testCreateWithNoDefinitionAndAllDefaults()
    {
        $component = new Component([
            'transports' => ['null'],
            'defaults' => [
                'from' => ['from@test.test' => 'from name'],
                'sender' => ['sender@test.test' => 'sender name'],
                'to' => ['to@test.test' => 'to name'],
                'cc' => ['cc@test.test' => 'cc name'],
                'bcc' => ['bcc@test.test' => 'bcc name'],
                'subject' => 'test subject',
                'isHtml' => false,
            ],
            'define' => [
                'test-mail' => [],
            ],
        ]);
        $mail = $component->create('test-mail');
        $this->assertInstanceOf(Mail::className(), $mail);
        $message = $mail->getMessage();
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals(['from@test.test' => 'from name'], $message->getFrom());
        $this->assertEquals(['sender@test.test' => 'sender name'], $message->getSender());
        $this->assertEquals(['to@test.test' => 'to name'], $message->getTo());
        $this->assertEquals(['cc@test.test' => 'cc name'], $message->getCc());
        $this->assertEquals(['bcc@test.test' => 'bcc name'], $message->getBcc());
        $this->assertEquals('test subject', $message->getSubject());
        $this->assertFalse($mail->getIsHtml());
    }

    public function testCreateWithFullDefinitionAndNoDefaults()
    {
        $component = new Component([
            'transports' => ['null'],
            'defaults' => [],
            'define' => [
                'test-mail' => [
                    'from' => ['from@test.test' => 'from name'],
                    'sender' => ['sender@test.test' => 'sender name'],
                    'to' => ['to@test.test' => 'to name'],
                    'cc' => ['cc@test.test' => 'cc name'],
                    'bcc' => ['bcc@test.test' => 'bcc name'],
                    'subject' => 'test subject',
                    'isHtml' => false,
                ],
            ],
        ]);
        $mail = $component->create('test-mail');
        $this->assertInstanceOf(Mail::className(), $mail);
        $message = $mail->getMessage();
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals(['from@test.test' => 'from name'], $message->getFrom());
        $this->assertEquals(['sender@test.test' => 'sender name'], $message->getSender());
        $this->assertEquals(['to@test.test' => 'to name'], $message->getTo());
        $this->assertEquals(['cc@test.test' => 'cc name'], $message->getCc());
        $this->assertEquals(['bcc@test.test' => 'bcc name'], $message->getBcc());
        $this->assertEquals('test subject', $message->getSubject());
        $this->assertFalse($mail->getIsHtml());
    }

    public function testTransportLog()
    {
        $component = new Component();
        $transport = $this->getMockBuilder('Swift_NullTransport')
            ->getMock();
        $transport->expects($this->exactly(1))
            ->method('registerPlugin')
            ->with($this->callback(function ($plugin) {
                $this->assertInstanceOf('Swift_Plugins_LoggerPlugin', $plugin);
                return true;
            }));
        $component->transports = [$transport];
        $component->transportLog = true;
        $component->getMailer();
    }
}