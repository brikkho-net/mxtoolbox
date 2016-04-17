<?php
/**
 * Nagios example - not tested now
 */
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

try {

    $test = new MxToolbox();

    /**
     * Configure MxToolbox
     */
    $test
        // path to the dig tool - required
        ->setDig('/usr/bin/dig')
        // set dns resolver - required
        ->setDnsResolver('127.0.0.1')
        // load default blacklists for dnsbl check - required in this example
        ->setBlacklists();

    /**
     * Check any IP address on all DNSBL
     */
    $test->checkIpAddressOnDnsbl('8.8.8.8');

    /**
     * Check any IP address on all DNSBL
     * Multiprocessing - much faster but experimental in v0.1.0
     */
    //$test->checkIpAddressOnDnsbl('8.8.8.8', true);


    /**
     * Nagios return POSIX code
     * https://nagios-plugins.org/doc/guidelines.html#AEN78
     * Positive check - return WARNING
     * All OK - return OK
     * Exception - return UNKNOWN
     */
    foreach ($test->getBlacklistsArray() as $list) {
        if($list['blPositive'])
            exit('ERR');
    }
    exit(0);

} catch (MxToolboxRuntimeException $e) {
    exit(3);
} catch (MxToolboxLogicException $e) {
    exit(3);
} catch (Exception $e) {
    exit(3);
}
