<?php
namespace MXTests;

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

class makeAliveBlacklistFileTest extends MxToolboxCaseTest {

	public function testMakeBlacklist() {
		try {
			$mxt = new MxToolbox(true,'/usr/bin/dig');
			$mxt->makeAliveBlacklistFile();
			$this->assertTrue( file_exists(dirname(__FILE__) . '/../../src/MxToolbox/blacklistsAlive.txt') );
			unset($mxt);
			$mxt = new MxToolbox();
			$mxt->makeAliveBlacklistFile();
		}
		catch (MxToolboxException $e) {
			$this->assertContains('File does not exist!', $e->getMessage());
		}
		
	}
	

}
