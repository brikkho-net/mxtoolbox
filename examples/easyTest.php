<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

class easyTest extends MxToolbox
{

    public $myBlacklist = array();

    public function __construct()
    {
        $this->myBlacklist = array(
            0 => 'zen.spamhaus.org',
            1 => 'zen2.spamhaus.org'
        );
        parent::__construct();
    }


    /**
     * Configure MXToolbox
     */
    protected function configure()
    {
        $this
            ->setDig('/usr/bin/dig')
            ->setDnsResolver('127.0.0.1')
            //->setDnsResolver('194.8.253.11')
            //->setDnsResolver('194.8.252.1')
            ->setBlacklists();
            //->setBlacklists($this->myBlacklist);
    }

    /**
     * Test IP address
     * @param mixed $addr
     */
    public function testMyIPAddress($addr)
    {

        try {
            $this->checkIpAddressOnDnsbl($addr);
            var_dump($this->getBlacklistsArray());

            /**
             * Clean old results before next test
             * TRUE = +check on response for all again
             * FALSE = only cleaning old results
             */
            $this->cleanBlacklistArray(false);
            // update blacklistAlive.txt
            //$this->updateAliveBlacklistFile();
            // get dns resolvers array
            //var_dump($this->getDnsResolvers());
            // get DIG path
            //var_dump($this->getDigPath());

        } catch (MxToolboxRuntimeException $e) {
            echo $e->getMessage();
        } catch (MxToolboxLogicException $e) {
            echo $e->getMessage();
        }
        // check IP address
        //	$mxt->checkAllrBLS($addr);
        /*
        * Show result
        * Structure:
        * []['blHostName'] = DNSBL host name
        * []['blPositive'] = true if IP addres have the positive check
        * []['blPositiveResult'][] = array of a URL addresses if IP address have the positive check
        * []['blResponse'] = true if DNSBL host name is alive and send test response before test
        */
        //	var_dump($mxt->getCheckResult());
    }

}

$test = new easyTest();
$test->testMyIPAddress('127.0.0.2');

