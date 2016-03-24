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
//            ->setDnsResolver('194.8.253.11')
            /*            ->setDnsResolver('194.8.252.1')*/
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
//            $this->checkIpAddressOnDnsbl($addr);
//            var_dump($this->getBlacklistsArray());
//            $stdin = fopen('php://stdin', 'r');
//            $response = fgetc($stdin);
//            fclose($stdin);
//            $this->cleanBlacklistArray();
            $this->updateAliveBlacklistFile();
//            var_dump($this->getBlacklistsArray());
            //$this->checkIpAddressOnDnsbl($addr);
            //var_dump($this->getBlacklistsArray());
            //var_dump($this->getDnsResolvers());
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
//$test->testMyIPAddress('127.0.0.2');
$test->testMyIPAddress('194.8.253.5');

