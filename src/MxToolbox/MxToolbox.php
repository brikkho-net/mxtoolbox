<?php
namespace MxToolbox;

use MxToolbox\DataGrid\MxToolboxDataGrid;
use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\FileSystem\BlacklistsHostnameFile;
use MxToolbox\NetworkTools\NetworkTools;

/**
 * MxToolBox - test your IP address on very known spam databases and blacklists
 *
 * @author Lubomir Spacek
 * @license MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 *
 * @version 0.0.2-dev
 *
 */
abstract class MxToolbox
{
    /** @var BlacklistsHostnameFile */
    private $fileSys;
    /** @var MxToolboxDataGrid */
    private $dataGrid;
    /** @var NetworkTools */
    private $netTool;
    
    /**
     * MxToolbox constructor.
     */
    public function __construct()
    {
       try {
           $this->netTool = new NetworkTools();
           $this->fileSys = new BlacklistsHostnameFile();
           $this->dataGrid = new MxToolboxDataGrid($this->fileSys, $this->netTool);
           $this->configure();
       } catch (MxToolboxLogicException $e) {
           echo $e->getMessage();
           exit(1);
       } catch (MxToolboxRuntimeException $e) {
           echo $e->getMessage();
           exit(1);
       }
    }

    /**
     * Configure MxToolbox.
     */
    abstract protected function configure();

    /**
     * Set dig path, etc: /usr/bin/dig
     * @param string $digPath
     * @return $this
     * @throws MxToolboxLogicException
     */
    protected function setDig($digPath) {
        $this->netTool->setDigPath($digPath);
        return $this;
    }

    /**
     * Set DNS resolver IP address (support for multiples push)
     * @param string $addr
     * @return $this
     * @throws MxToolboxLogicException
     */
    protected function setDnsResolver($addr) {
        $this->netTool->setDnsResolverIP($addr);
        return $this;
    }

    /**
     * @param array $ownBlacklist optional, default nothing
     * @return $this
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    protected function setBlacklists(&$ownBlacklist = null) {
        try {
            $this->dataGrid->buildBlacklistHostNamesArray($ownBlacklist);
            return $this;
        }
        catch (MxToolboxRuntimeException $e) {
            if ($e->getCode() == 400) {
                $this->dataGrid->buildNewBlacklistHostNames();
                return $this;
            }
            return $e;
        }
    }

    /**
     * Get DNS resolvers
     * @return array
     */
    protected function getDnsResolvers() {
        return $this->netTool->getDnsResolvers();
    }

    /**
     * Get DIG path
     * @return string
     */
    protected function getDigPath() {
        return $this->netTool->getDigPath();
    }

    /**
     * Get blacklists array.
     * @return array
     * @throws MxToolboxLogicException
     */
    protected function &getBlacklistsArray()
    {
        return $this->dataGrid->getTestResultArray();
    }

    /**
     * Refresh alive blacklists host names in static file (/blacklistsAlive.txt)
     * @return $this
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    protected function updateAliveBlacklistFile() {
        $this->fileSys->deleteAliveBlacklist();
        $this->setBlacklists();
        return $this;
    }

    /**
     * Clean blacklist array from previous search
     * @return $this
     */
    protected function cleanBlacklistArray() {
        $this->dataGrid->cleanPrevResults();
        return $this;
    }

    /**
     * Check IP address on all DNSBL servers
     * @param string $addr
     * @return $this
     */
    protected function checkIpAddressOnDnsbl(&$addr) {
        $this->netTool->checkAllDnsbl($addr, $this->dataGrid->getTestResultArray());
        return $this;
    }
    
    // not ok under this
    
    /**
     * Get PTR record
     * @return mixed string|bool
     */
    public function getPTR()
    {
        if (!empty($this->recordPTR))
            return $this->recordPTR;
        return false;
    }

    /**
     * Get domain name
     * @return mixed string|bool
     */
    public function getDomainName()
    {
        if (!empty($this->domainName))
            return $this->domainName;
        return false;
    }

    /**
     * Get MX records from domain name
     * @param string $hostName
     * @return mixed - ARRAY with MX records or FALSE
     */
    public function getMXRecords($hostName)
    {
        if ($this->checkHostName($hostName)) {
            $ptr = dns_get_record($hostName, DNS_MX);
            if (isset($ptr[0]['target'])) {
                $mxRecords = array();
                foreach ($ptr as $mx)
                    $mxRecords[] = $mx['target'];
                return $mxRecords;
            }
        }
        return false;
    }

    /**
     * Check if IP address have a PTR record
     * @param string $addr
     * @return boolean
     */
    public function checkExistPTR($addr)
    {
        $this->recordPTR = '';
        $this->domainName = '';
        if (!$this->validateIPAddress($addr))
            return false;
        $ptr = dns_get_record($this->reverseIP($addr) . '.in-addr.arpa.', DNS_PTR);
        if (isset($ptr[0]['target'])) {
            $regs = array();
            $this->recordPTR = $ptr[0]['target'];
            if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $ptr[0]['target'], $regs))
                $this->domainName = $regs['domain'];
            return true;
        }
        return false;
    }


}
