<?php

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'vendor/autoload.php';

/*
 * seznam vsech blaclistu ?
 * http://multirbl.valli.org/list/
 */

try {
	$addr = '194.8.253.5';
	$mxt = new MxToolbox();
	/**
	 * refresh DNSBL alive host names file list (run only one or few times a day)
	 */
	$mxt->makeAliveBlacklistFile();

	/**
	 * Do any only if IP address have a reverse PTR record
	 */
	if ( $mxt->checkExistPTR($addr) ) {
		// print the reverse PTR record
		echo $mxt->getPTR().PHP_EOL;
		// print the domain name from reverse PTR record
		echo $mxt->getDomainName().PHP_EOL;
		// print list of a MX records of the domain name
		print_r($mxt->getMXRecords($mxt->getDomainName()));
		// search IP address in all DNSBL if the IP address have a reverse PTR record and domain name from PTR have any same MX record in DNS
		if (! array_search($mxt->getPTR(), $mxt->getMXRecords($mxt->getDomainName())) === false )
			$mxt->checkAllrBLS('194.8.253.5');
		/**
		 * Print result array
		 * Structure:
		 * []['blHostName'] = DNSBL host name
		 * []['blPositive'] = true if IP addres have the positive check
		 * []['blPositiveResult'][] = array of URL address if IP address have the positive chech (some DNSBL not supported return any URL)
		 * []['blResponse'] = true if DNSBL host name is alive and send test response before test
		 */
		print_r($mxt->getCheckResult());
	}
} catch ( MxToolboxException $e ) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}