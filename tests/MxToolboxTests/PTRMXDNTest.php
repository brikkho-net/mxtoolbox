<?php
namespace MXTests;

use MxToolbox\MxToolbox;
//use MxToolbox\Exception\MxToolboxException;

class PTRMXDNTest extends MxToolboxCaseTest {

	public function testPTR() {
		$mxt = new MxToolbox();
		$this->assertTrue( $mxt->checkExistPTR('8.8.8.8') );
		$this->assertInternalType( 'string', $mxt->getPTR() );
		$this->assertFalse( $mxt->checkExistPTR('127.0.0.4') );
		$this->assertFalse( $mxt->checkExistPTR('127.0.0.256') );
		$this->assertFalse( $mxt->getPTR() );
	}
	
	public function testMxRecord() {
		$mxt = new MxToolbox();
		$this->assertTrue( $mxt->checkExistPTR('8.8.8.8') );
		$this->assertInternalType( 'array', $mxt->getMXRecords( $mxt->getDomainName() ) );
		$this->assertFalse ( $mxt->checkExistPTR('127.0.0.256') );
		$this->assertFalse ( $mxt->getMXRecords( $mxt->getDomainName() ) );
	}

	public function testDomainName() {
		$mxt = new MxToolbox();
		$this->assertTrue( $mxt->checkExistPTR('8.8.8.8') );
		$this->assertInternalType( 'string', $mxt->getDomainName() );
		$this->assertFalse ( $mxt->checkExistPTR('127.0.0.256') );
		$this->assertFalse ( $mxt->getDomainName() );
	}

}
