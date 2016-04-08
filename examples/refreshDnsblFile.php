<?php
/**
 * Refresh blacklist alive file
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
        //->setDnsResolver('8.8.8.8')
        //->setDnsResolver('8.8.4.4')
        ->setDnsResolver('127.0.0.1');

    /*
     * Update the blacklistAlive.txt file - ! time-consuming process !
     */
    $test->updateAliveBlacklistFile();

    /*
     * Get new test array prepared for check (without any test results)
     */
    var_dump($test->getBlacklistsArray());

} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}
