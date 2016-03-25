<?php
namespace MXTests;

/**
 * Class MxToolboxTest
 * @package MXTests
 */
class MxToolboxTest extends \PHPUnit_Framework_TestCase
{

    /** @expectedException MxToolbox\Exceptions\MxToolboxLogicException */
    public function testSetDig() {
        $mxt = new MxTestTool('','8.8.8.8');
        unset($mxt);
    }

    /** @expectedException MxToolbox\Exceptions\MxToolboxLogicException */
    public function testSetDnsResolver() {
        $mxt = new MxTestTool('/usr/bin/dig','');
        unset($mxt);
    }

    /** test domain information like PTR record, Domain name, MX record */
    public function testDomainInformation() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $this->assertFalse( $mxt->getDomainInformation('8.8.8.83') );
        $dnsRecords = $mxt->getDomainInformation('8.8.8.8');
        $this->assertInternalType('array', $dnsRecords);
        $this->assertInternalType('array', $dnsRecords['mxRecords']);
        $this->assertEquals(3, count($dnsRecords));
    }

    /** test is mail server - simple */
    public function testIsMailServer() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $this->assertFalse( $mxt->isMailServer('8.8.8.8') );
        $this->assertTrue( $mxt->isMailServer('194.8.253.5') );
    }

    /** test setBlacklist() */
    public function testSetBlacklistsDefaultAndOwn() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $this->assertInstanceOf('MXTests\MxTestTool', $mxt->setBlacklists());
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
        $myBlacklist = array(
            0 => 'zen.spamhaus.org',
            1 => 'xbl.spamhaus.org'
        );
        $this->assertInstanceOf('MXTests\MxTestTool', $mxt->setBlacklists($myBlacklist));
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
        $this->assertCount(2, $mxt->getBlacklistsArray());
    }

    /** @expectedException MxToolbox\Exceptions\MxToolboxLogicException */
    public function testGetBlacklistsArrayException() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $mxt->getBlacklistsArray();
    }

    /** test get blacklist array */
    public function testGetBlacklistsArray() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $mxt->setBlacklists();
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
    }

    /** test dig path, file exist, string contain dig */
    public function testGetDigPath() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $this->assertInternalType('string', $mxt->getDigPath());
        $this->assertFileExists($mxt->getDigPath());
        $this->assertContains('dig', $mxt->getDigPath());
    }

    /** test get resolvers array */
    public function testGetDnsResolvers() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $this->assertInternalType('array', $mxt->getDnsResolvers());

    }

    /** test refresh alive blacklist file */
    public function testUpdateAliveBlackFile() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $this->assertInstanceOf('MXTests\MxTestTool', $mxt->updateAliveBlacklistFile());
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
    }


    /** test check IP address in DNSBL and clean results for next test */
    public function testCheckIpDnsblCleanArray() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $addr = '127.0.0.2';
        $this->assertInstanceOf('MXTests\MxTestTool',
            $mxt
                ->setBlacklists()
                ->checkIpAddressOnDnsbl($addr)
        );
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
        $this->assertInstanceOf('MXTests\MxTestTool', $mxt->cleanBlacklistArray());
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
    }

    /** @expectedException MxToolbox\Exceptions\MxToolboxLogicException */
    public function testCheckIpDnsblCleanArrayException() {
        $mxt = new MxTestTool('/usr/bin/dig','8.8.8.8');
        $addr = '8.8.8.8';
        $this->assertInstanceOf('MXTests\MxTestTool', $mxt->checkIpAddressOnDnsbl($addr));
    }
}
