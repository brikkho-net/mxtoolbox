<?php
/**
 * MxToolbox basic tests
 */
namespace MXTests;

use MxToolbox\MxToolbox;

/**
 * Class MxToolboxTest
 */
class MxToolboxTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test set dig
     * @expectedException \MxToolbox\Exceptions\MxToolboxLogicException
     */
    public function testSetDig()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt);
        unset($mxt);
    }

    /**
     * @expectedException \MxToolbox\Exceptions\MxToolboxLogicException
     */
    public function testSetDnsResolver()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '');
        unset($mxt);
    }

    /**
     * test domain information like PTR record, Domain name, MX record
     */
    public function testDomainInformation()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $this->assertFalse($mxt->getDomainInformation('8.8.8.83'));
        $dnsRecords = $mxt->getDomainInformation('8.8.8.8');
        $this->assertInternalType('array', $dnsRecords);
        $this->assertInternalType('array', $dnsRecords['mxRecords']);
        $this->assertEquals(4, count($dnsRecords));
    }

    /**
     * mail server diagnostics
     */
    public function testSmtpDiagnostics()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $info = $mxt->getSmtpDiagnosticsInfo(
            '194.8.253.5',
            'best-hosting.cz',
            'mxtool@best-hosting.cz',
            'test@example.com'
        );
        $this->assertInternalType('array', $info);
    }

    /**
     * test setBlacklist()
     */
    public function testSetBlacklistsDefaultAndOwn()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $this->assertInstanceOf('MxToolbox\MxToolbox', $mxt->setBlacklists());
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
        $myBlacklist = array(
            0 => 'zen.spamhaus.org',
            1 => 'xbl.spamhaus.org'
        );
        $this->assertInstanceOf('MxToolbox\MxToolbox', $mxt->setBlacklists($myBlacklist));
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
        $this->assertCount(2, $mxt->getBlacklistsArray());
    }

    /**
     * @expectedException \MxToolbox\Exceptions\MxToolboxLogicException
     */
    public function testGetBlacklistsArrayException()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $mxt->getBlacklistsArray();
    }

    /**
     * test get blacklist array
     */
    public function testGetBlacklistsArray()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $mxt->setBlacklists();
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
    }

    /**
     * test dig path, file exist, string contain dig
     */
    public function testGetDigPath()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $this->assertInternalType('string', $mxt->getDigPath());
        $this->assertFileExists($mxt->getDigPath());
        $this->assertContains('dig', $mxt->getDigPath());
    }

    /**
     * test get resolvers array
     */
    public function testGetDnsResolvers()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $this->assertInternalType('array', $mxt->getDnsResolvers());

    }

    /**
     * test refresh alive blacklist file
     */
    public function testUpdateAliveBlackFile()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $this->assertInstanceOf('MxToolbox\MxToolbox', $mxt->updateAliveBlacklistFile());
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
    }

    /**
     * test check IP address in DNSBL and clean results for next test
     */
    public function testCheckIpDnsblCleanArray()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $this->assertInstanceOf('MxToolbox\MxToolbox',
            $mxt
                ->setBlacklists()
                ->checkIpAddressOnDnsbl('127.0.0.2')
        );
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
        $this->assertInstanceOf('MxToolbox\MxToolbox', $mxt->cleanBlacklistArray());
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
    }

    /**
     * test check IP address in DNSBL with python multiprocess script and clean results for next test
     */
    public function testCheckIpDnsblMultiprocess()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $this->assertInstanceOf('MxToolbox\MxToolbox',
            $mxt
                ->setBlacklists()
                ->checkIpAddressOnDnsbl('127.0.0.2',true)
        );
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
        $this->assertInstanceOf('MxToolbox\MxToolbox', $mxt->cleanBlacklistArray());
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
    }

    /**
     * @expectedException \MxToolbox\Exceptions\MxToolboxLogicException
     */
    public function testCheckIpDnsblCleanArrayException()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $this->assertInstanceOf('MxToolbox\MxToolbox', $mxt->checkIpAddressOnDnsbl('8.8.8.8'));
    }

    /**
     * test user defined path to the blacklist files
     */
    public function testUserDefinedBlacklistFilePath()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $mxt->setBlacklistFilePath(dirname(__FILE__) . '/../../vendor/mxtoolbox-blacklists/mxtoolbox-blacklists/');
        $this->assertInstanceOf('MxToolbox\MxToolbox', $mxt->setBlacklists());
        $this->assertInstanceOf('MxToolbox\MxToolbox', $mxt->checkIpAddressOnDnsbl('8.8.8.8'));
        $this->assertInternalType('array', $mxt->getBlacklistsArray());
    }

    /**
     * Test user defined bad path to the blacklist files
     * @expectedException \MxToolbox\Exceptions\MxToolboxRuntimeException
     */
    public function testUserDefinedBlacklistFilePathException()
    {
        $mxt = new MxToolbox();
        $this->basicConfigureMxToolbox($mxt, '/usr/bin/dig', '8.8.8.8');
        $mxt->setBlacklistFilePath('./foo/');
        $mxt->setBlacklists();
    }

    /**
     * Basic configuration
     * @param MxToolbox $mxt
     * @param string|bool $dns
     * @param string|bool $dig
     */
    private function basicConfigureMxToolbox(MxToolbox $mxt, $dig = false, $dns = false)
    {
        $mxt->setDig($dig)->setDnsResolver($dns);
    }

}
