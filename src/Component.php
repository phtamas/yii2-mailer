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
class Component extends BaseComponent
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

    /**
     * @param string $id
     * @return Mail
     */
    public function create($id)
    {
        $definition = $this->define[$id];
        $mail = new Mail(
            $id,
            $this->getMailer(),
            array_replace_recursive($this->defaults, $definition)
        );
        if (isset($this->viewPath)) {
            $mail->setViewPath($this->viewPath);
        }
        return $mail;
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