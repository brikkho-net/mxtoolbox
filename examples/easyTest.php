<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

/**
 * Class easyTest
 */
class easyTest extends MxToolbox
{

    /**
     * Configure MXToolbox
     * configure() is abstract function and must by implemented
     */
    protected function configure()
    {
        $this
            // path to the dig tool
            ->setDig('/usr/bin/dig')

            // multiple resolvers is allowed
            //->setDnsResolver('8.8.8.8')
            //->setDnsResolver('8.8.4.4')
            ->setDnsResolver('127.0.0.1')

            // load default blacklists (from the file: blacklistAlive.txt)
            ->setBlacklists();
    }

    /**
     * Test IP address
     * @param string $addr
     */
    public function testMyIPAddress($addr)
    {

        try {
 
            // Checks IP address on all DNSBL
            $this->checkIpAddressOnDnsbl($addr);
 
            /*
             * getBlacklistsArray() structure:
             * []['blHostName'] = dnsbl hostname
             * []['blPositive'] = true if IP address have the positive check
             * []['blPositiveResult'] = array() array of a URL addresses if IP address have the positive check
             * []['blResponse'] = true if DNSBL host name is alive and send test response before test
             * []['blQueryTime'] = false or response time of a last dig query
             */
            var_dump($this->getBlacklistsArray());

        } catch (MxToolboxRuntimeException $e) {
            echo $e->getMessage();
        } catch (MxToolboxLogicException $e) {
            echo $e->getMessage();
        }
    }

}

$test = new easyTest();
$test->testMyIPAddress('8.8.8.8');
