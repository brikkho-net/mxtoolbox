<?php

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../src/MxToolbox/autoload.php';

try {
	/**
	 * IP address for test
	 * @link https://tools.ietf.org/html/rfc5782 cap. 5
	 */
	$addr = '127.0.0.2';
	/**
	 * Create MxToolbox object
	 */
	$mxt = new MxToolbox();
    /**
     * check IP address
     */
	$mxt->checkAllrBLS($addr);
	/**
	 * Show result
	 * Structure:
	 * []['blHostName'] = DNSBL host name
	 * []['blPositive'] = true if IP addres have the positive check
	 * []['blPositiveResult'][] = array of URL address if IP address have the positive chech (some DNSBL not supported return any URL)
	 * []['blResponse'] = true if DNSBL host name is alive and send test response before test
	 */
	var_dump($mxt->getCheckResult());

} catch ( MxToolboxException $e ) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}