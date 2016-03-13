<?php

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../src/MxToolbox/autoload.php';

try {
	/**
	 * real IP address of a mail server for test
	 */
	$addr = '';
	/**
	 * Create MxToolbox object
	 */
	$mxt = new MxToolbox(true,'/usr/bin/dig');
	/**
	 * Do any only if IP address have a reverse PTR record
	 */
	if ( $mxt->checkExistPTR($addr) ) {
		/**
		 * search IP address in all DNSBL if the IP address have a reverse PTR record
		 * and domain name from PTR have any MX record corresponding with the PTR.
		 *
		 * getPTR() and getDomainName() return FALSE without calling checkExistPTR()
		 */
		if (! array_search($mxt->getPTR(), $mxt->getMXRecords($mxt->getDomainName())) === false ) {
			$mxt->checkAllrBLS($addr);
			/**
			* Print result array
			* Structure:
			* []['blHostName'] = DNSBL host name
			* []['blPositive'] = true if IP addres have the positive check
		 	* []['blPositiveResult'][] = array of URL address if IP address have the positive chech (some DNSBL not supported return any URL)
		 	* []['blResponse'] = true if DNSBL host name is alive and send test response before test
		 	*/
			var_dump($mxt->getCheckResult());
		}
	}

} catch ( MxToolboxException $e ) {
	echo 'Caught exception: '.  $e->getMessage(), PHP_EOL;
}