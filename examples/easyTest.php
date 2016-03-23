<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

class easyTest extends MxToolbox
{

    /**
     * Configure MXToolbox
     */
    protected function configure()
    {
        $this
            ->setDigPath('/usr/bin/dig')
            ->pushDNSResolverIP('127.0.0.1')
            ->buildBlacklistHostnamesArray();
    }

    /**
     * Test IP address
     * @param $addr
     */
    public function testMyIPAddress($addr)
    {

        try {

            var_dump($this->getTestBlacklistsArray());

            // check IP address
            //	$mxt->checkAllrBLS($addr);
            /*
            * Show result
            * Structure:
            * []['blHostName'] = DNSBL host name
            * []['blPositive'] = true if IP addres have the positive check
            * []['blPositiveResult'][] = array of URL address if IP address have the positive check
            * []['blResponse'] = true if DNSBL host name is alive and send test response before test
            */
            //	var_dump($mxt->getCheckResult());
        } catch (MxToolboxRuntimeException $e) {
            echo $e->getMessage() . PHP_EOL;
        } catch (MxToolboxLogicException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

    }

}

$test = new easyTest();
$test->testMyIPAddress('194.8.253.5');