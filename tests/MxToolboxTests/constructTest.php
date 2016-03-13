<?php
namespace MXTests;

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

class constructTest extends MxToolboxCaseTest {

	public function testConstruct() {
		try {
			$mxt = new MxToolbox('/usr/bin/dig');
			unset($mxt);
			$mxt = new MxToolbox();
			unset($mxt);
			$mxt = new MxToolbox('/usr/DIG');
		}
		catch (MxToolboxException $e) {
			$this->assertContains('File does not exist!', $e->getMessage());
		}
		
	}

}
