<?php
namespace phtamas\yii2\mailer\tests;

use phtamas\yii2\mailer\Component;

class ComponentTest extends \PHPUnit_Framework_TestCase
{
    private $messageFullConfiguration = [
        'from' => ['from@test.test' => 'from name'],
        'sender' => ['sender@test.test' => 'sender name'],
        'to' => ['to@test.test' => 'to name'],
        'cc' => ['cc@test.test' => 'cc name'],
        'bcc' => ['bcc@test.test' => 'bcc name'],
        'subject' => 'test subject',
        'html' => true,
    ];

    public function messageFullConfigurationCallback($message)
    {
        $this->assertInstanceOf('Swift_Message', $message);
        /* @var $message \Swift_Message */
        $this->assertEquals(['from@test.test' => 'from name'], $message->getFrom());
        $this->assertEquals(['sender@test.test' => 'sender name'], $message->getSender());
        $this->assertEquals(['to@test.test' => 'to name'], $message->getTo());
        $this->assertEquals(['cc@test.test' => 'cc name'], $message->getCc());
        $this->assertEquals(['bcc@test.test' => 'bcc name'], $message->getBcc());
        $this->assertEquals('test subject', $message->getSubject());
        $this->assertEquals('multipart/alternative', $message->getContentType());
        $this->assertEquals('html body', $message->getBody());
        $this->assertArrayHasKey(0, $message->getChildren());
        $this->assertEquals('text/plain', $message->getChildren()[0]->getContentType());
        $this->assertEquals('plain text body', $message->getChildren()[0]->getBody());
        return true;
    }

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

    public function testGetViewPathDefaultReturnValue()
    {
        $component = new Component(['transports' => ['null']]);
        $this->assertEquals(\Yii::$app->viewPath . DIRECTORY_SEPARATOR . 'mails', $component->getViewPath());
    }

    public function testGetViewPath()
    {
        $component = new Component([
            'transports' => ['null'],
            'viewPath' => '/mailer/view/path'
        ]);
        $this->assertEquals('/mailer/view/path', $component->getViewPath());
    }

    public function testSendUnconfiguredWithAllDefaultsAndNoOptions()
    {
        $component = new Component([
            'transports' => ['null'],
            'defaults' => $this->messageFullConfiguration,
            'define' => [
                'test-mail' => [],
            ],
        ]);

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
        $component->setView($view);

        $mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback([$this, 'messageFullConfigurationCallback']));
        $component->setMailer($mailer);
        $component->send('test-mail', $templateData);
    }

    public function testSendFullyConfiguredWithNoDefaultsAndNoOptions()
    {
        $component = new Component([
            'transports' => ['null'],
            'define' => [
                'test-mail' => $this->messageFullConfiguration,
            ],
        ]);

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
        $component->setView($view);

        $mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback([$this, 'messageFullConfigurationCallback']));
        $component->setMailer($mailer);
        $component->send('test-mail', $templateData);
    }

    public function testSendUnconfiguredWithNoDeafultsAndAllOptions()
    {
        $component = new Component([
            'transports' => ['null'],
            'define' => [
                'test-mail' => [],
            ],
        ]);

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
        $component->setView($view);

        $mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback([$this, 'messageFullConfigurationCallback']));
        $component->setMailer($mailer);
        $component->send('test-mail', $templateData, $this->messageFullConfiguration);
    }

    public function testSendWithNonExistingDefinition()
    {
        $component = new Component([
            'transports' => ['null'],
        ]);
        $this->setExpectedException('yii\base\InvalidConfigException');
        $component->send('non-existing-id', []);
    }

    public function testSendWithInvalidDefinitionType()
    {
        $component = new Component([
            'transports' => ['null'],
            'define' => [
                'test-mail' => new \stdClass(),
            ],
        ]);
        $this->setExpectedException('yii\base\InvalidConfigException');
        $component->send('test-mail', []);
    }

    public function testSendPlainTextOnly()
    {
        $component = new Component([
            'transports' => ['null'],
            'define' => [
                'test-mail' => array_replace_recursive($this->messageFullConfiguration, ['html' => false]),
            ],
        ]);

        $templateData = ['key' => 'value'];

        $view = $this->getMockBuilder('yii\web\View')
            ->disableOriginalConstructor()
            ->getMock();
        $view->method('render')
            ->willReturn('plain text body');
        $view->expects($this->once())
            ->method('render')
            ->with($this->equalTo('plain-text/test-mail'), $this->equalTo($templateData));
        $component->setView($view);

        $mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($message) {
                $this->assertInstanceOf('Swift_Message', $message);
                /* @var $message \Swift_Message */
                $this->assertEquals('text/plain', $message->getContentType());
                $this->assertEquals('plain text body', $message->getBody());
                $this->assertCount(0, $message->getChildren());
                return true;
            }));
        $component->setMailer($mailer);
        $component->send('test-mail', $templateData);
    }
}