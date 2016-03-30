<?php
/**
 * SMTP checks tool
 *
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 */
namespace MxToolbox\NetworkTools;

use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;

/**
 * Class SmtpServerChecks
 * @package MxToolbox\NetworkTools
 */
class SmtpServerChecks
{

    /** SMTP line break */
    const CRLF = "\r\n";

    /** @var resource stream resource or FALSE */
    private $smtpConnection = false;

    /** @var int SMTP port */
    private $smtpPort = 25;

    /** @var array SMTP response */
    private $smtpResponses = array();

    /** @var array final results of a SMT server responses */
    private $finalResults = array();

    /** @var string My hostname for SMTP command */
    private $myHostName;

    /** @var string test MAIL FROM: */
    private $emailFrom;

    /** @var string test MAIL TO: */
    private $emailRcptTo;

    /**
     * SmtpServerChecks constructor.
     * @param string $myHostName Hostname corresponding with IP address where is this script running
     * @param string $mailFrom Email address corresponding with your domain
     * @param string $mailRcptTo Non existing email like this: 'test@example.net'
     */
    public function __construct($myHostName, $mailFrom, $mailRcptTo)
    {
        if (!$this->isEmail($mailFrom) || !$this->isEmail($mailRcptTo))
            throw new MxToolboxLogicException('Non valid email format.');
        if (empty($myHostName))
            throw new MxToolboxLogicException('Missing argument myHostName.');
        $this->myHostName = $myHostName;
        $this->emailFrom = $mailFrom;
        $this->emailRcptTo = $mailRcptTo;
        $this->setResultArray();
    }

    /**
     * Connect to the SMTP server
     * @param string $addr IP address for test
     * @return $this
     * @throws MxToolboxRuntimeException
     */
    public function setSmtpConnect($addr)
    {
        $this->smtpConnection = stream_socket_client($addr . ':' . $this->smtpPort, $errno, $errstr, 5, STREAM_CLIENT_CONNECT);
        if (is_resource($this->smtpConnection)) {
            $this->smtpResponses[] = fgets($this->smtpConnection, 4096);
            return $this;
        }
        throw new MxToolboxRuntimeException('Connect to server failed.');
    }

    /**
     * Get SMTP array with responses
     * @return array|bool FALSE if array is blank
     */
    public function getSmtpResponses()
    {
        if (count($this->smtpResponses) > 0)
            return $this->smtpResponses;
        return false;
    }

    /**
     * Starting the conversation. Server wants to use the extended SMTP (ESMTP) protocol.
     * @return $this
     * @throws MxToolboxRuntimeException
     */
    public function setEhloResponse()
    {
        if (is_resource($this->smtpConnection)) {
            $this->writeCommand('EHLO', $this->myHostName);
            $this->smtpResponses[] = array_filter(explode(self::CRLF, $this->readCommand()));
            return $this;
        }
        throw new MxToolboxRuntimeException('Invalid connection');
    }

    /**
     * Set MAIL FROM response
     * @return $this
     */
    public function setFromResponse()
    {
        if (is_resource($this->smtpConnection)) {
            $this->writeCommand('MAIL FROM:', $this->emailFrom);
            $this->smtpResponses[] = array_filter(explode(self::CRLF, $this->readCommand()));
            return $this;
        }
        throw new MxToolboxRuntimeException('Invalid connection');
    }

    /**
     * Set RCPT TO response
     * @return $this
     */
    public function setRcptToResponse()
    {
        if (is_resource($this->smtpConnection)) {
            $this->writeCommand('RCPT TO:', $this->emailFrom);
            $this->smtpResponses[] = array_filter(explode(self::CRLF, $this->readCommand()));
            return $this;
        }
        throw new MxToolboxRuntimeException('Invalid connection');
    }

    /**
     * write command to stream
     * @param $command
     * @param string $param
     */
    private function writeCommand($command, $param = '')
    {
        fwrite($this->smtpConnection, $command . " " . $param . self::CRLF);
    }

    /**
     * read command from stream
     * @return mixed
     */
    private function readCommand()
    {
        return fread($this->smtpConnection, 4096);
    }

    /**
     * Close SMTP connection
     * @return $this
     */
    public function closeSmtpConnection()
    {
        if (is_resource($this->smtpConnection)) {
            fwrite($this->smtpConnection, 'QUIT' . self::CRLF);
            fclose($this->smtpConnection);
        }
        return $this;
    }

    /**
     * Initialize final results array
     */
    private function setResultArray()
    {
        $this->finalResults['rDnsMismatch'] = false;
        $this->finalResults['validHostname'] = false;
        $this->finalResults['bannerCheck'] = false;
        $this->finalResults['tls'] = false;
        $this->finalResults['connectTime'] = false;
        $this->finalResults['openRelay'] = false;
        return $this;
    }

    /**
     * Is string email format
     * @param string $email
     * @return bool
     */
    private function isEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return false;
        return true;
    }

    //TODO: parse responses, set final result
}
