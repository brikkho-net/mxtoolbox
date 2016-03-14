<?php

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../src/MxToolbox/autoload.php';

try {

	/**
	 * Create MxToolbox object
	 * by default blacklist is not loaded
	 */
	$mxt = new MxToolbox('/usr/bin/dig');
	/**
	 * Push one or more IP address of your DNS resolvers
	 */
	$mxt->pushDNSResolverIP('127.0.0.1');
	$mxt->pushDNSResolverIP('192.168.1.1');
	/**
	 * Load blacklist
	 */
	$mxt->loadBlacklist();
	/**
	 * refresh DNSBL alive host names file list (run only one or few times a day)
	 */
	$mxt->makeAliveBlacklistFile();

} catch ( MxToolboxException $e ) {
	echo 'Caught exception: ',  $e->getMessage(), PHP_EOL;
}