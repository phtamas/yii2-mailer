<?php
namespace phtamas\yii2\mailer;

use yii\base\Object;
use yii\base\View;
use yii\base\ViewContextInterface;

/**
 * @property string|array $from
 * @property string|array $sender
 * @property string|array $to
 * @property string|array $cc
 * @property string|array $bcc
 * @property bool $isHtml
 */
class Mail extends Object implements ViewContextInterface
{
    private $id;

    private $mailer;

    private $_viewPath;

    private $isHtml = true;

    private $_message;

    private $_view;

    /**
     * @param string $id
     * @param \Swift_Mailer $mailer
     * @param array $config
     */
    public function __construct($id, \Swift_Mailer $mailer, $config = [])
    {
        $this->id = $id;
        $this->mailer = $mailer;
        parent::__construct($config);
    }

    /**
     * @return string
     */
    public function getViewPath()
    {
        if (!isset($this->_viewPath)) {
            $this->_viewPath =  \Yii::$app->viewPath . DIRECTORY_SEPARATOR . 'mails';
        }
        return $this->_viewPath;
    }

    /**
     * @param string $viewPath
     */
    public function setViewPath($viewPath)
    {
        $this->_viewPath = $viewPath;
    }

    /**
     * @return string
     */
    public function getHtmlViewName()
    {
        return 'html/' . $this->id;
    }

    /**
     * @return string
     */
    public function getPlainTextViewName()
    {
        return 'plain-text/' . $this->id;
    }

    /**
     * @return \Swift_Message
     */
    public function getMessage()
    {
        if (!isset($this->_message)) {
            $this->_message = new \Swift_Message();
        }
        return $this->_message;
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
     * @return string|array
     */
    public function getForm()
    {
        return $this->getMessage()->getFrom();
    }

    /**
     * @param string|array $addresses
     * @param string $name
     * @return $this
     */
    public function setFrom($addresses, $name = null)
    {
        $this->getMessage()->setFrom($addresses, $name);
        return $this;
    }

    /**
     * @return string
     */
    public function getSender()
    {
        return $this->getMessage()->getSender();
    }

    /**
     * @param string $address
     * @param string $name
     * @return $this
     */
    public function setSender($address, $name = null)
    {
        $this->getMessage()->setSender($address, $name);
        return $this;
    }

    /**
     * @return array
     */
    public function getTo()
    {
        return $this->getMessage()->getTo();
    }

    /**
     * @param string|array $addresses
     * @param string $name
     * @return $this
     */
    public function setTo($addresses, $name = null)
    {
        $this->getMessage()->setTo($addresses, $name);
        return $this;
    }

    /**
     * @return array
     */
    public function getCc()
    {
        return $this->getMessage()->getCc();
    }

    /**
     * @param string|array $addresses
     * @param string $name
     * @return $this
     */
    public function setCc($addresses, $name = null)
    {
        $this->getMessage()->setCc($addresses, $name);
        return $this;
    }

    /**
     * @return array
     */
    public function getBcc()
    {
        return $this->getMessage()->getBcc();
    }

    /**
     * @param string|array $addresses
     * @param string $name
     * @return $this
     */
    public function setBcc($addresses, $name = null)
    {
        $this->getMessage()->setBcc($addresses, $name);
        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->getMessage()->getSubject();
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->getMessage()->setSubject($subject);
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsHtml()
    {
        return $this->isHtml;
    }

    /**
     * @param bool $isHtml
     * @return $this
     */
    public function setIsHtml($isHtml)
    {
        $this->isHtml = $isHtml;
        return $this;
    }

    /**
     * @param array $data
     */
    public function send(array $data = [])
    {
        $message = $this->getMessage();
        $plainTextBody = $this->getView()->render($this->getPlainTextViewName(), $data);
        if ($this->isHtml) {
            $htmlBody = $this->getView()->render($this->getHtmlViewName(), $data);
            $message->setBody($htmlBody, 'text/html');
            $message->addPart($plainTextBody, 'text/plain');
        } else {
            $message->setBody($plainTextBody, 'text/plain');
        }
        $this->mailer->send($message);
    }
}