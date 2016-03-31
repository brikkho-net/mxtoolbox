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

        if (!$this->netTool->ipValidator($addr))
            throw new MxToolboxLogicException('Non valid IP address.');

        if (!$this->isEmail($mailFrom) || !$this->isEmail($mailRcptTo))
            throw new MxToolboxLogicException('Non valid email format.');

        if (empty($myHostName) || !$this->netTool->ipValidator($myHostName))
            throw new MxToolboxLogicException('Missing or bad argument myHostName.');

        $this->addr = $addr;
        $this->myHostName = $myHostName;
        $this->emailFrom = $mailFrom;
        $this->emailRcptTo = $mailRcptTo;
        $this->setResultArray();
    }

    /**
     * @return array with final results
     */
    public function getSmtpServerDiagnostic()
    {
        if (!$this->setSmtpConnect($this->addr))
            return $this->finalResults;
        $this
            ->setEhloResponse()
            ->setFromResponse()
            ->setRcptToResponse()
            ->closeSmtpConnection()
            ->parseResults();
        return $this->finalResults;
    }

    /**
     * Connect to the SMTP server
     * @param string $addr IP address for test
     * @return true | array when connection failed
     */
    private function setSmtpConnect($addr)
    {
        $this->smtpConnection = stream_socket_client($addr . ':' . $this->smtpPort, $errno, $errstr,
            $this->connTimeout, STREAM_CLIENT_CONNECT);
        if (is_resource($this->smtpConnection)) {
            $this->smtpResponses['connection'] = fgets($this->smtpConnection, 4096);
            return true;
        }
        $this->finalResults['errors'] = 'Unable to connect to ' . $addr . ':25 (Timeout | No route to host).';
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
        $this->finalResults['rDnsMismatch']['state'] = false;
        $this->finalResults['rDnsMismatch']['info'] = '';

        $this->finalResults['validHostname']['state'] = false;
        $this->finalResults['validHostname']['info'] = '';

        $this->finalResults['bannerCheck']['state'] = false;
        $this->finalResults['bannerCheck']['info'] = '';

        $this->finalResults['tls']['state'] = false;
        $this->finalResults['tls']['info'] = '';

        $this->finalResults['connectTime']['state'] = false;
        $this->finalResults['connectTime']['info'] = '';

        $this->finalResults['openRelay']['state'] = true;
        $this->finalResults['openRelay']['info'] = '';

        $this->finalResults['allResponses']['state'] = false;
        $this->finalResults['allResponses']['info'] = '';

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


    /**
     * Parse all responses from SMTP stream
     */
    private function parseResults()
    {
        $parser = new SmtpDiagnosticParser();
        $info = $this->netTool->getDomainDetailInfo($this->addr);

        // check TLS
        $this->finalResults['tls']['state'] = $parser->isTls($this->smtpResponses['ehlo']);
        $this->finalResults['tls']['info'] = 'ERR - TLS is not supported!';
        if ($this->finalResults['tls']['state']) {
            $this->finalResults['tls']['info'] = 'OK - TLS is supported.';
        }

        // Reverse DNS Mismatch: If the A record of the hostname did not match the PTR = TRUE
        $this->checkRDnsMismatch($info, $parser);

        // Reverse DNS Hostname validation
        $this->checkValidHostname($info, $parser);

        // next
        //TODO: parse responses, set final result

    }

    /**
     * Check valid hostname
     * @param array $info
     * @param SmtpDiagnosticParser $parser
     * @return $this
     */
    private function checkValidHostname(&$info, SmtpDiagnosticParser &$parser)
    {
        if (isset($info['ptrRecord']) && $this->netTool->isDomainName($info['ptrRecord'])) {
            $this->finalResults['validHostname']['state'] = $parser->isValidHostname($this->smtpResponses['ehlo'], $info['ptrRecord']);
        }
        $this->finalResults['validHostname']['info'] = 'OK - rDNS is a valid hostname';
        if (!$this->finalResults['validHostname']['state']) {
            $this->finalResults['validHostname']['info'] = 'rDNS is not a valid hostname';
        }
        return $this;

    }

    /**
     * Check rDNSMismatch
     * @param $info
     * @param SmtpDiagnosticParser $parser
     * @return $this
     */
    private function checkRDnsMismatch(&$info, SmtpDiagnosticParser &$parser)
    {
        if (isset($info['ptrRecord']) && $this->netTool->isDomainName($info['ptrRecord'])) {
            $this->finalResults['rDnsMismatch']['state'] = $parser->isReverseDnsMismatch(
                $this->addr, dns_get_record($info['ptrRecord'], DNS_A)
            );
            $this->finalResults['rDnsMismatch']['info'] = 'OK - ' . $this->addr . ' resolves to ' . $info['ptrRecord'];
        }
        if (!$this->finalResults['rDnsMismatch']['state']) {
            $this->finalResults['rDnsMismatch']['info'] = 'ERR - Cannot resolve address: ' . $this->addr;
        }
        return $this;
    }

}

