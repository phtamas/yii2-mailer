<?php
namespace phtamas\yii2\mailer;

use yii\base\Component as BaseComponent;
use yii\base\InvalidConfigException;
use yii\base\View;
use yii\base\ViewContextInterface;
use yii\log\Logger;

/**
 * @property \Swift_Mailer $mailer
 * @property View $view
 * @property Logger $logger
 */
class Component extends BaseComponent implements ViewContextInterface
{
    /** @var array */
    public $transports;

    /** @var null|string */
    public $viewPath;

    /** @var array */
    public $defaults = [];

    /** @var array */
    public $define;

    /** @var bool */
    public $transportLog = false;

    /** @var \Swift_Mailer */
    private $_mailer;

    /** @var View*/
    private $_view;

    /** @var Logger */
    private $_logger;
    /**
     * @return \Swift_Mailer
     */
    public function getMailer()
    {
        if (!isset($this->_mailer)) {
            $this->_mailer = $this->createMailer();
        }
        return $this->_mailer;
    }

    /**
     * @param \Swift_Mailer $mailer
     */
    public function setMailer(\Swift_Mailer $mailer)
    {
        $this->_mailer = $mailer;
    }

    /**
     * @return View
     */
    public function getView()
    {
        if (!isset($this->_view)) {
            $this->_view = \Yii::$app->view;
        }
        return $this->_view;
    }

    public function setView(View $view)
    {
        $this->_view = $view;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        if (!isset($this->_logger)) {
            $this->_logger = \Yii::getLogger();
        }
        return $this->_logger;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->_logger = $logger;
    }

    public function getViewPath()
    {
        if (is_null($this->viewPath)) {
            return \Yii::$app->viewPath . DIRECTORY_SEPARATOR . 'mails';
        }
        return $this->viewPath;
    }

    /**
     * @param string $id
     * @param array $data
     * @param array $options
     * @throws InvalidConfigException
     */
    public function send($id, array $data = [], array $options = [])
    {
        if (!isset($this->define[$id])) {
            throw new InvalidConfigException(sprintf(
                'Definition is missing for message id "%s".',
                is_scalar($id) ? $id : gettype($id)
            ));
        }
        $definition = $this->define[$id];
        if (!is_array($definition)) {
            throw new InvalidConfigException(sprintf(
                'Definition for message id "%s" expected to be an array of option => value pairs, %s given.',
                $id,
                gettype($definition)
            ));
        }
        $config = array_replace_recursive($this->defaults, $definition, $options);
        $message = new \Swift_Message();
        if (isset($config['from'])) {
            $message->setFrom($config['from']);
        }
        if (isset($config['sender'])) {
            $message->setSender($config['sender']);
        }
        if (isset($config['to'])) {
            $message->setTo($config['to']);
        }
        if (isset($config['cc'])) {
            $message->setCc($config['cc']);
        }
        if (isset($config['bcc'])) {
            $message->setBcc($config['bcc']);
        }
        if (isset($config['subject'])) {
            $message->setSubject($config['subject']);
        }
        $plainTextBody = $this->getView()->render('plain-text/' . $id, $data);
        if (isset($config['html']) && $config['html']) {
            $htmlBody = $this->getView()->render('html/' . $id, $data);
            $message->setBody($htmlBody, 'text/html');
            $message->addPart($plainTextBody, 'text/plain');
        } else {
            $message->setBody($plainTextBody, 'text/plain');
        }
        $this->getMailer()->send($message);
    }

    private function createMailer()
    {
        if (count($this->transports) === 1) {
            $transport = $this->createTransport($this->transports[0]);
        } else {
            $transports = [];
            foreach ($this->transports as $transportConfig) {
                $transports[] = $this->createTransport($transportConfig);
            }
            $transport = new \Swift_FailoverTransport();
            $transport->setTransports($transports);
        }
        $mailer = new \Swift_Mailer($transport);
        if ($this->transportLog) {
            $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin(new LogAdapter($this->getLogger())));
        }
        return $mailer;
    }

    private function createTransport($config)
    {
        if ($config instanceof \Swift_Transport) {
            return $config;
        }
        if (is_array($config)) {
            $type = $config['type'];
            unset($config['type']);
        } else {
            $type = $config;
            $config = null;
        }
        if ($type === 'smtp') {
            return $this->createSmtpTransport($config);
        }
        if ($type === 'mail') {
            return $this->createMailTransport($config);
        }
        if ($type === 'null') {
            return new \Swift_NullTransport();
        }
    }

    private function createMailTransport(array $config = null)
    {
        $transport = new \Swift_MailTransport();
        if (!$config) {
            return $transport;
        }
        if (isset($config['extraParams'])) {
            $transport->setExtraParams($config['extraParams']);
        }
        return $transport;
    }

    private function createSmtpTransport(array $config = null)
    {
        $transport = new \Swift_SmtpTransport();
        if (!$config) {
            return $transport;
        }
        if (isset($config['host'])) {
            $transport->setHost($config['host']);
        }
        if (isset($config['port'])) {
            $transport->setPort($config['port']);
        }
        if (isset($config['encryption'])) {
            $transport->setEncryption($config['encryption']);
        }
        if (isset($config['username'])) {
            $transport->setUsername($config['username']);
        }
        if (isset($config['password'])) {
            $transport->setPassword($config['password']);
        }
        return $transport;
    }
}