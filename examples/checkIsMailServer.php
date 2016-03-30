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

        /**
         * simply test (If IP address have any PTR record and this PTR record is in MX)
         * @deprecated deprecated since version 0.0.3
         */
//        var_dump($this->isMailServer($addr));
        
        // Get SMTP server responses
        var_dump($this->getSmtpDiagnostics(
            $addr,
            'vps-nx.best-hosting.cz',
            'mxtool@best-hosting.cz',
            'test@example.com'
        ));

    }

}

try {
    $test = new checkIsMailServer();
    $test->testMyIPAddress('194.8.253.10');
} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}
