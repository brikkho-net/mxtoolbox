<?php
namespace MXTests;

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

class checkDNSBLTest extends MxToolboxCaseTest {

	public function testDNSBLBadConstructor() {
		try {
			$mxt = new MxToolbox('/usr/DIG');
		}
		catch (MxToolboxException $e) {
			$this->assertContains('File does not exist!', $e->getMessage());
		}
	}

	public function testDNSBL() {
		$mxt = new MxToolbox('/usr/bin/dig');
		$mxt->pushDNSResolverIP('8.8.8.8');
		$mxt->loadBlacklist();
		$this->assertTrue( $mxt->checkAllrBLS('127.0.0.2') );
		$this->assertInternalType( 'array', $mxt->getBlackLists() );
		$this->assertInternalType( 'array', $mxt->getCheckResult() );
	}

	public function testDNSBLNoValidIP() {
		$mxt = new MxToolbox('/usr/bin/dig');
		$mxt->pushDNSResolverIP('8.8.8.8');
		$mxt->loadBlacklist();
		$this->assertFalse( $mxt->checkAllrBLS('127.0.0.300') );
		$this->assertFalse( $mxt->getCheckResult() );
	}
	
	public function testDNSBLNoPushResolverIP() {
		try {
			$mxt = new MxToolbox('/usr/bin/dig');
			$mxt->loadBlacklist();
			$this->assertFalse( $mxt->checkAllrBLS('127.0.0.2') );
		}
		catch (MxToolboxException $e) {
			$this->assertContains('No DNS resolver here!', $e->getMessage());
		}
	}
	
	public function testDNSBLNoDigPath() {
		try {
			$mxt = new MxToolbox();
			$mxt->pushDNSResolverIP('8.8.8.8');
			$mxt->loadBlacklist();
			$this->assertFalse( $mxt->checkAllrBLS('127.0.0.2') );
		}
		catch (MxToolboxException $e) {
			$this->assertContains('DIG path:', $e->getMessage());
		}
	}
	
}
