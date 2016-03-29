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
    const CRLF = '\r\n';

    /** @var string stream resource or FALSE */
    private $smtpConnection = false;

    /** @var int SMTP port */
    private $smtpPort = 25;

    /** @var array SMTP response */
    private $smtpResponse = array();

    /** @var string My hostname for SMTP command */
    private $myHostName;

    /**
     * Connect to the SMTP server
     * @param $addr
     * @return $this
     * @throws MxToolboxRuntimeException
     */
    public function setSmtpConnect($addr,$myHostName)
    {
        $this->myHostName = $myHostName;
        $socket_context = stream_context_create();
        if ($this->smtpConnection = stream_socket_client($addr . ':' . $this->smtpPort,
            $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $socket_context)) 
        {
            $this->smtpResponse[] = fread($this->smtpConnection, 4096);
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

    public function testSmtpServer() {
        if ($this->smtpConnection) {
            fwrite($this->smtpConnection, "EHLO " . $this->myHostName . self::CRLF);
            echo fread($this->smtpConnection, 4096);
//        $response = explode('\n', fread($this->smtpConnection, 4096));
//        print_r($response);
            print_r($this->smtpResponse);
            return $this;
        }
        throw new MxToolboxLogicException('Invalid connection');
    }

}