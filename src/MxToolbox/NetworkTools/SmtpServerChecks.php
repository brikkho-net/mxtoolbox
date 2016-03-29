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
    private $smtpResponse = array();

    /** @var string My hostname for SMTP command */
    private $myHostName;

    /**
     * Connect to the SMTP server
     * @param string $addr IP address for test
     * @param string $myHostName Hostname corresponding with IP address where is this script running
     * @return $this
     * @throws MxToolboxRuntimeException
     */
    public function setSmtpConnect($addr,$myHostName)
    {
        $this->myHostName = $myHostName;
        $socket_context = stream_context_create();
        $this->smtpConnection = stream_socket_client($addr . ':' . $this->smtpPort, 
            $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $socket_context);
        if (is_resource($this->smtpConnection)) 
        {
            $this->smtpResponse[] = fgets($this->smtpConnection, 4096);
            return $this;
        }
        throw new MxToolboxRuntimeException('Connect to server failed.');
    }

    /**
     * Get SMTP array with responses
     * @return array|bool FALSE if array is blank
     */
    public function getSmtpResponse()
    {
        if (count($this->smtpResponse) > 0)
            return $this->smtpResponse;
        return false;
    }

    /**
     * Starting the conversation. Server wants to use the extended SMTP (ESMTP) protocol.
     * @return $this
     * @throws MxToolboxRuntimeException
     */
    public function testEhloSmtpServer() {
        if (is_resource($this->smtpConnection)) {
            fwrite($this->smtpConnection, "EHLO " . $this->myHostName . self::CRLF);
            $this->smtpResponse[] = array_filter(explode(self::CRLF, fread($this->smtpConnection, 4096)));

            $this->closeSmtpConnection();

            return $this;
        }
        throw new MxToolboxRuntimeException('Invalid connection');
    }

    /**
     * Close SMTP connection
     * @return $this
     */
    protected function closeSmtpConnection()
    {
        if (is_resource($this->smtpConnection)) {
            fwrite($this->smtpConnection, 'QUIT' . self::CRLF);
            fclose($this->smtpConnection);
        }
        return $this;
    }

}