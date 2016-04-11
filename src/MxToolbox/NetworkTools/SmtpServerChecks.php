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

    /** @var NetworkTools object */
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
     * @param string $addr IP address or hostname for test
     * @param string $myHostName real HostName of the server where script is running (must be resolved to IP address)
     * @param string $mailFrom Any testing mail address (domain is same as hostname)
     * @param string $mailRcptTo non exist email address as test@example.com
     */
    public function __construct(NetworkTools $netTool, $addr, $myHostName, $mailFrom, $mailRcptTo)
    {
        $this->netTool = $netTool;

        if (!$this->netTool->ipValidator($addr))
            throw new MxToolboxLogicException('The value: ' . $addr . ' is not valid an IP address or domain name.');

        if (!$this->isEmail($mailFrom) || !$this->isEmail($mailRcptTo))
            throw new MxToolboxLogicException('Non valid email format.');

        if (empty($myHostName) || !$this->netTool->ipValidator($myHostName))
            throw new MxToolboxLogicException('Missing or bad argument myHostName.');

        $this->addr = $this->netTool->getIpAddressFromDomainName($addr);
        $this->myHostName = $myHostName;
        $this->emailFrom = $mailFrom;
        $this->emailRcptTo = $mailRcptTo;
        $this->setFinalResultsArray();
    }

    /**
     * Get SMTP diagnostics information.
     *
     *  Return information in array(
     *      ['rDnsMismatch']['state'] => boolean,
     *      ['rDnsMismatch']['info'] => string,
     *      ['validHostname']['state'] => boolean,
     *      ['validHostname']['info'] => string,
     *      ['bannerCheck']['state'] => boolean,
     *      ['bannerCheck']['info'] => string,
     *      ['tls']['state'] => boolean,
     *      ['tls']['info'] => string,
     *      ['openRelay']['state'] => boolean,
     *      ['openRelay']['info'] => string,
     *      ['errors']['state'] => boolean,
     *      ['errors']['info'] => string,
     *      ['allResponses'] => array()
     *      ['allResponses'] => array(
     *          [connection] => string,
     *          [ehlo] => array(),
     *          [mailFrom] => string,
     *          [rcptTo] => string,
     *          ),
     *  );
     *
     * @return array
     */
    public function getSmtpServerDiagnostic()
    {
        if (!$this->setSmtpConnect($this->addr)) {
            $this->closeSmtpConnection();
            return $this->finalResults;
        }
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
     * @return boolean
     */
    private function setSmtpConnect($addr)
    {
        $this->smtpConnection = @stream_socket_client($addr . ':' . $this->smtpPort, $errno, $errstr,
            $this->connTimeout, STREAM_CLIENT_CONNECT);
        if (is_resource($this->smtpConnection)) {
            stream_set_timeout($this->smtpConnection, $this->connTimeout);
            $this->smtpResponses['connection'] = $this->readCommand();
            $info = stream_get_meta_data($this->smtpConnection);
            if ($info['timed_out']) {
                $this->finalResults['errors']['info'] = 'SMTP command (waiting timeout).';
                return false;
            }
            $this->finalResults['errors']['state'] = false;
            return true;
        }
        $this->finalResults['errors']['info'] = 'Unable to connect to ' . $addr . ':25 (Timeout | No route to host).';
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
            $this->smtpResponses['mailFrom'] = $this->readCommand();
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
            $this->smtpResponses['rcptTo'] = $this->readCommand();
            return $this;
        }
        throw new MxToolboxRuntimeException('Invalid connection');
    }

    /**
     * Write command to the SMTP stream
     * @param string $command
     * @param string $param
     */
    private function writeCommand($command, $param = '')
    {
        fwrite($this->smtpConnection, $command . " " . $param . self::CRLF);
    }

    /**
     * Read command from the SMTP stream
     * @return mixed
     */
    private function readCommand()
    {
        return fread($this->smtpConnection, 4096);
    }

    /**
     * Close the SMTP connection
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
    private function setFinalResultsArray()
    {
        $this->finalResults['rDnsMismatch']['state'] = false;
        $this->finalResults['rDnsMismatch']['info'] = '';
        $this->finalResults['validHostname']['state'] = false;
        $this->finalResults['validHostname']['info'] = '';
        $this->finalResults['bannerCheck']['state'] = false;
        $this->finalResults['bannerCheck']['info'] = '';
        $this->finalResults['tls']['state'] = false;
        $this->finalResults['tls']['info'] = '';
//        $this->finalResults['connectTime']['state'] = false;
//        $this->finalResults['connectTime']['info'] = '';
        $this->finalResults['openRelay']['state'] = true;
        $this->finalResults['openRelay']['info'] = '';
        $this->finalResults['errors']['state'] = true;
        $this->finalResults['errors']['info'] = '';
        $this->finalResults['allResponses'] = false;
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
     * Parse all responses from the SMTP stream
     * @return $this
     */
    private function parseResults()
    {
        $parser = new SmtpDiagnosticParser();
        $info = $this->netTool->getDomainDetailInfo($this->addr);

        // Check TLS available
        // Reverse DNS Mismatch: If the A record of the hostname did not match the PTR = TRUE
        // Reverse DNS Hostname validation
        // Reverse DNS checks in SMTP Banner
        // Check open relay
        $this
            ->checkTls($parser)
            ->checkRDnsMismatch($info, $parser)
            ->checkValidHostname($info, $parser)
            ->checkSmtpBanner($info, $parser)
            ->checkOpenRelay($parser);

        // set all responses to final result
        if (count($this->smtpResponses) > 0)
            $this->finalResults['allResponses'] = $this->smtpResponses;
        return $this;
    }

    /**
     * Check if is TLS supported
     * @param SmtpDiagnosticParser $parser
     * @return $this
     */
    private function checkTls($parser)
    {
        $this->finalResults['tls']['state'] = $parser->isTls($this->smtpResponses['ehlo']);
        $this->finalResults['tls']['info'] = 'OK - TLS is supported.';
        if (!$this->finalResults['tls']['state'])
            $this->finalResults['tls']['info'] = 'ERR - TLS is not supported!';
        return $this;
    }

    /**
     * Check valid hostname
     * @param array $info
     * @param SmtpDiagnosticParser $parser
     * @return $this
     */
    private function checkValidHostname($info, SmtpDiagnosticParser $parser)
    {
        if (isset($info['ptrRecord']) && $this->netTool->isDomainName($info['ptrRecord']))
            $this->finalResults['validHostname']['state'] = $parser->isValidHostname(
                $this->smtpResponses['ehlo'], $info['ptrRecord']
            );
        $this->finalResults['validHostname']['info'] = 'OK - rDNS is a valid hostname.';
        if (!$this->finalResults['validHostname']['state'])
            $this->finalResults['validHostname']['info'] = 'ERR - rDNS is not a valid hostname!';
        return $this;

    }

    /**
     * Check rDNSMismatch
     * @param array $info
     * @param SmtpDiagnosticParser $parser
     * @return $this
     */
    private function checkRDnsMismatch($info, SmtpDiagnosticParser $parser)
    {
        if (isset($info['ptrRecord']) && $this->netTool->isDomainName($info['ptrRecord'])) {
            $this->finalResults['rDnsMismatch']['state'] = $parser->isReverseDnsMismatch(
                $this->addr, dns_get_record($info['ptrRecord'], DNS_A)
            );
            $this->finalResults['rDnsMismatch']['info'] = 'OK - ' . $this->addr . ' resolves to ' . $info['ptrRecord'];
        }
        if (!$this->finalResults['rDnsMismatch']['state'])
            $this->finalResults['rDnsMismatch']['info'] = 'ERR - Cannot resolve address: ' . $this->addr;
        return $this;
    }

    /**
     * Check PTR matches in SMTP banner
     * @param array $info
     * @param SmtpDiagnosticParser $parser
     * @return $this
     */
    private function checkSmtpBanner($info, SmtpDiagnosticParser $parser)
    {
        if (isset($info['ptrRecord']) && $this->netTool->isDomainName($info['ptrRecord'])) {
            $this->finalResults['bannerCheck']['state'] = $parser->isReverseDnsInBanner(
                $this->smtpResponses['connection'], $info['ptrRecord']
            );
            $this->finalResults['bannerCheck']['info'] = 'OK - Reverse DNS matches SMTP banner.';
        }
        if (!$this->finalResults['bannerCheck']['state'])
            $this->finalResults['bannerCheck']['info'] = 'ERR - Reverse DNS does not match SMTP banner!';
        return $this;
    }

    /**
     * Check open relay access
     * @param SmtpDiagnosticParser $parser
     * @return $this
     */
    private function checkOpenRelay(SmtpDiagnosticParser $parser)
    {
        if (isset($this->smtpResponses['rcptTo']))
            $this->finalResults['openRelay']['state'] = $parser->isOpenRelay($this->smtpResponses['rcptTo'][0]);
        $this->finalResults['openRelay']['info'] = 'OK - Relay access denied.';
        if (!$this->finalResults['openRelay']['state'])
            $this->finalResults['openRelay']['info'] = 'ERR - Relay access open!';
        return $this;
    }

}

