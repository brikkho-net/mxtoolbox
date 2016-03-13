<?php

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../src/MxToolbox/autoload.php';

try {

	/**
	 * Create MxToolbox object
	 * by default blacklist is not loaded
	 */
	$mxt = new MxToolbox();
	/**
	 * refresh DNSBL alive host names file list (run only one or few times a day)
	 */
	$mxt->makeAliveBlacklistFile();

} catch ( MxToolboxException $e ) {
	echo 'Caught exception: ',  $e->getMessage(), PHP_EOL;
}