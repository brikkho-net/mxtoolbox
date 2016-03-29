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

        // Is correct setting as mail server - simply test (The PTR record for this $addr exist in MX records)
//        var_dump($this->isMailServer($addr));
        
        // Get SMTP server responses
        $this->getSmtpServerResponse($addr);

    }

}

try {
    $test = new checkIsMailServer();
    $test->testMyIPAddress('194.8.253.5');
} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}
