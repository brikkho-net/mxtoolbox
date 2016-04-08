<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

try {
    $test = new MxToolbox();
    // Configure MxToolbox
    $test
        // path to the dig tool - required
        ->setDig('/usr/bin/dig')
        // set dns resolver - required
        //->setDnsResolver('8.8.8.8')
        //->setDnsResolver('8.8.4.4')
        ->setDnsResolver('127.0.0.1')
        // set user path to the blacklist files - optional
        ->setBlacklistFilePath(dirname(__FILE__) . '/../vendor/mxtoolbox-blacklists/mxtoolbox-blacklists/')
        // load default blacklists for dnsbl check - optional
        ->setBlacklists();

    /*
     * Get test array prepared for check if you need (without any test results)
     */
    //var_dump($this->getBlacklistsArray());

    /*
     * Check IP address on all DNSBL
     */
    $test->checkIpAddressOnDnsbl('8.8.8.8');

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

    var_dump($test->getBlacklistsArray());
    /*
      * Cleaning old results - REQUIRED only in loop before next test
      * TRUE = check responses for all DNSBL again (default value)
      * FALSE = only cleaning old results ([blResponse] => true)
      */
    $test->cleanBlacklistArray(false);


} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}