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
    
    /** @var int Connection timeout in seconds */
    private $connTimeout = 15;

    /** @var array SMTP response */
    private $smtpResponses = array();

    /** @var array final results of a SMT server responses */
    private $finalResults = array();
    
    /** @var NetworkTools */
    private $netTool;
    
    /** @var string IP Address of a SMTP server */
    private $addr;

    /** @var string My hostname for SMTP command */
    private $myHostName;

    /** @var string test MAIL FROM: */
    private $emailFrom;

    /** @var string test MAIL TO: */
    private $emailRcptTo;
    
    /**
     * SmtpServerChecks constructor.
     * @param NetworkTools $netTool
     * @param string $addr IP address of a SMTP server
     * @param string $myHostName Hostname corresponding with IP address where is this script running
     * @param string $mailFrom Email address corresponding with your domain
     * @param string $mailRcptTo Non existing email like this: 'test@example.net'
     */
    public function __construct(NetworkTools $netTool, $addr, $myHostName, $mailFrom, $mailRcptTo)
    {
        $this->netTool = $netTool;
        if (!$this->netTool->validateIPAddress($addr))
            throw new MxToolboxLogicException('Non valid IP address.');
        if (!$this->isEmail($mailFrom) || !$this->isEmail($mailRcptTo))
            throw new MxToolboxLogicException('Non valid email format.');
        if (empty($myHostName))
            throw new MxToolboxLogicException('Missing argument myHostName.');
        $this->addr = $addr;
        $this->myHostName = $myHostName;
        $this->emailFrom = $mailFrom;
        $this->emailRcptTo = $mailRcptTo;
        $this->setResultArray();
    }

    /**
     * @return array|bool
     */
    public function getSmtpServerDiagnostic() {
        if (is_array($this->setSmtpConnect($this->addr)))
            return $this->finalResults;
        $this
            ->setEhloResponse()
            ->setFromResponse()
            ->setRcptToResponse()
            ->closeSmtpConnection();
        return $this->getSmtpResponses();
    }
    
    /**
     * Connect to the SMTP server
     * @param string $addr IP address for test
     * @return $this | array when connection failed
     */
    private function setSmtpConnect($addr)
    {
        $this->smtpConnection = @stream_socket_client($addr . ':' . $this->smtpPort, $errno, $errstr, 
            $this->connTimeout, STREAM_CLIENT_CONNECT);
        if (is_resource($this->smtpConnection)) {
            $this->smtpResponses['connection'] = fgets($this->smtpConnection, 4096);
            return $this;
        }
        $this->finalResults['errors'] = 'Unable to connect to '.$addr.':25 after ' . $this->connTimeout .
            ' seconds. (No route to host).';
        return $this->finalResults;
    }

    /**
     * Get SMTP array with responses
     * @return array|bool FALSE if array is blank
     */
    private function getSmtpResponses()
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
    private function setEhloResponse()
    {
        if (is_resource($this->smtpConnection)) {
            $this->writeCommand('EHLO', $this->myHostName);
            $this->smtpResponses['ehlo'] = array_filter(explode(self::CRLF, $this->readCommand()));
            return $this;
        }
        throw new MxToolboxRuntimeException('Invalid connection');
    }

    /**
     * Set MAIL FROM response
     * @return $this
     */
    private function setFromResponse()
    {
        if (is_resource($this->smtpConnection)) {
            $this->writeCommand('MAIL FROM:', $this->emailFrom);
            $this->smtpResponses['mailFrom'] = array_filter(explode(self::CRLF, $this->readCommand()));
            return $this;
        }
        throw new MxToolboxRuntimeException('Invalid connection');
    }

    /**
     * Set RCPT TO response
     * @return $this
     */
    private function setRcptToResponse()
    {
        if (is_resource($this->smtpConnection)) {
            $this->writeCommand('RCPT TO:', $this->emailFrom);
            $this->smtpResponses['rcptTo'] = array_filter(explode(self::CRLF, $this->readCommand()));
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
    private function closeSmtpConnection()
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
        $this->finalResults['allResponses'] = false;
        $this->finalResults['errors'] = false;
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
