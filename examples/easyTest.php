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

    /** @var array - my own blacklist hostnames array */
    public $myBlacklist = array();

    /**
     * easyTest constructor.
     */
    public function __construct($myBlacklist)
    {
        if(is_array($myBlacklist))
            $this->myBlacklist = $myBlacklist;
        // MxToolbox construct
        parent::__construct();
    }


    /**
     * Configure MXToolbox
     * configure() is abstract function and must by implemented
     */
    protected function configure()
    {
        $this
            // path to the dig tool
            ->setDig('/usr/bin/dig')

            // multiple set is allowed
            ->setDnsResolver('127.0.0.1')
            //->setDnsResolver('8.8.8.8')
            //->setDnsResolver('8.8.4.4')

            // load default blacklists
            //->setBlacklists();

            // load your own blacklist array (will be auto validate on response)    
            ->setBlacklists($this->myBlacklist);
    }

    /**
     * Test IP address
     * @param string $addr
     */
    public function testMyIPAddress($addr)
    {

        try {
            //$this->checkIpAddressOnDnsbl($addr);
            /*
             * getBlacklistsArray() structure:
             * []['blHostName'] = dnsbl hostname
             * []['blPositive'] = true if IP addres have the positive check
             * []['blPositiveResult'] = array() array of a URL addresses if IP address have the positive check
             * []['blResponse'] = true if DNSBL host name is alive and send test response before test
             * []['blQueryTime'] = false or response time of a last dig query
             */
            //var_dump($this->getBlacklistsArray());

            /* Cleaning old results before next test
             * TRUE = check responses for all DNSBL again (default value)
             * FALSE = only cleaning old results (response = true)
             */
            $this->cleanBlacklistArray(false);

            // update the blacklistAlive.txt file
            //$this->updateAliveBlacklistFile();

            // get array with dns resolvers
            //var_dump($this->getDnsResolvers());

            // get DIG path
            //var_dump($this->getDigPath());
            
            /* Get additional iformation for IP address
             * Array structure:
             * ['domainName']
             * ['ptrRecord']
             * ['mxRecords'][array]
             */
            var_dump($this->getDomainInformation($addr));

        } catch (MxToolboxRuntimeException $e) {
            echo $e->getMessage();
        } catch (MxToolboxLogicException $e) {
            echo $e->getMessage();
        }
    }

}

$myBlacklist = array(
    0 => 'zen.spamhaus.org',
    1 => 'xbl.spamhaus.org'
);

$test = new easyTest($myBlacklist);
$test->testMyIPAddress('41.71.171.23');
$test->testMyIPAddress('194.8.253.5');


