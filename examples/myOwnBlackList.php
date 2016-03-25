<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

/**
 * Class myOwnBlackList
 */
class myOwnBlackList extends MxToolbox
{

    /** @var array - my own blacklist with DNSBL host names array */
    public $myBlacklist = array();

    /**
     * easyTest constructor.
     */
    public function __construct($myBlacklist)
    {
        if(is_array($myBlacklist) && count($myBlacklist) > 0)
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

            // multiple resolvers is allowed
            //->setDnsResolver('8.8.8.8')
            //->setDnsResolver('8.8.4.4')
            ->setDnsResolver('127.0.0.1')

            // load your own blacklist array (will be auto validate on DNSBL response)    
            ->setBlacklists($this->myBlacklist);
    }

    /**
     * Test IP address with my own blacklist
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

            /* Cleaning old results before next test
             * TRUE = check responses for all DNSBL again (default value)
             * FALSE = only cleaning old results (response = true)
             */
            $this->cleanBlacklistArray(false);

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

$test = new myOwnBlackList($myBlacklist);
$test->testMyIPAddress('8.8.8.8');


