<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

/**
 * Class example
 */
class example extends MxToolbox
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
        /*
         * Get test array prepared for check (without any test results)
         */
        //var_dump($this->getBlacklistsArray());

        /*
         * Check IP address on all DNSBL
         */
        $this->checkIpAddressOnDnsbl($addr);

        /*
         *  Get the same array but with a check results
         * 
         *  Return structure:
         *  []['blHostName'] = dnsbl hostname
         *  []['blPositive'] = true if IP address have the positive check
         *  []['blPositiveResult'] = array() array of a URL addresses if IP address have the positive check
         *  []['blResponse'] = true if DNSBL host name is alive or DNSBL responded during the test
         *  []['blQueryTime'] = false or response time of a last dig query
         */

        var_dump($this->getBlacklistsArray());

        /*
         * Cleaning old results - REQUIRED only in loop before next test
         * TRUE = check responses for all DNSBL again (default value)
         * FALSE = only cleaning old results ([blResponse] => true)
         */
        $this->cleanBlacklistArray(false);

        // Get SMTP server diagnostics responses
        // '64.12.91.197' is any public SMTP server
        var_dump($this->getSmtpDiagnosticsInfo(
            '64.12.91.197',
            'google.com',
            'mxtool@example.com',
            'test@example.com'
        ));

        /* Get additional information for IP address
         * Array structure:
         * ['domainName']
         * ['ptrRecord']
         * ['mxRecords'][array]
         */
        var_dump($this->getDomainInformation($addr));

        // get array with dns resolvers
        var_dump($this->getDnsResolvers());

        // get DIG path
        var_dump($this->getDigPath());

    }

}

try {
    $test = new example();
    $test->testMyIPAddress('8.8.8.8');
    $test->testMyIPAddress('8.8.4.4');
} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}