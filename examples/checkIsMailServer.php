<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

/**
 * Class checkIsMailServer
 */
class checkIsMailServer extends MxToolbox
{

    /**
     * Configure MXToolbox
     * configure() is abstract function and must by implemented
     */
    public function configure()
    {
        $this
            // path to the dig tool
            ->setDig('/usr/bin/dig')
            // multiple resolvers is allowed
            //->setDnsResolver('8.8.8.8')
            //->setDnsResolver('8.8.4.4')
            ->setDnsResolver('127.0.0.1');
    }

    /**
     * Test IP address
     * @param string $addr
     */
    public function testMyIPAddress($addr)
    {

        // Get SMTP server diagnostics responses
        var_dump($this->getSmtpDiagnosticsInfo(
            $addr,
            'google.com',
            'mxtool@example.com',
            'test@example.com'
        ));

    }

}

try {
    $test = new checkIsMailServer();
    // '64.12.91.197' is any public SMTP server
    $test->testMyIPAddress('64.12.91.197');
} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}
