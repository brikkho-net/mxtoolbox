<?php
namespace MxToolbox;

use MxToolbox\NetworkTools\NetworkTools;
use MxToolbox\DataGrid\MxToolboxDataGrid;
use MxToolbox\FileSystem\BlacklistsHostnameFile;
use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;

/**
 * Abstract Class MxToolBox
 *
 * @package MxToolbox
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 * @version 0.0.2
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
     * @throws MxToolboxLogicException
     * @throws MxToolboxRuntimeException
     */
    public function __construct()
    {
        $this->netTool = new NetworkTools();
        $this->fileSys = new BlacklistsHostnameFile();
        $this->dataGrid = new MxToolboxDataGrid($this->fileSys, $this->netTool);
        $this->configure();
    }

    /**
     * Configure MxToolbox.
     */
    abstract public function configure();

    /**
     * Set dig path, etc: /usr/bin/dig
     * @param string $digPath
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function setDig($digPath)
    {
        $this->netTool->setDigPath($digPath);
        return $this;
    }

    /**
     * Set DNS resolver IP address (support for multiples push)
     * @param string $addr
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function setDnsResolver($addr)
    {
        $this->netTool->setDnsResolverIP($addr);
        return $this;
    }

    /**
     * @param array $ownBlacklist optional, default nothing
     * @return $this
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    public function setBlacklists(&$ownBlacklist = null)
    {
        try {
            $this->dataGrid->buildBlacklistHostNamesArray($ownBlacklist);
            return $this;
        } catch (MxToolboxRuntimeException $e) {
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
    public function getDnsResolvers()
    {
        return $this->netTool->getDnsResolvers();
    }

    /**
     * Get DIG path
     * @return string
     */
    public function getDigPath()
    {
        return $this->netTool->getDigPath();
    }

    /**
     * Get blacklists array.
     * @return array
     * @throws MxToolboxLogicException
     */
    public function &getBlacklistsArray()
    {
        return $this->dataGrid->getTestResultArray();
    }

    /**
     * Get some additional information about IP address (PTR record, Domain name, MX records )
     * Array structure: ['domainName'],['ptrRecord'],['mxRecords'][array]
     * @param $addr - ip address
     * @return array|bool - return array or FALSE if no information here.
     */
    public function getDomainInformation($addr)
    {
        $info = $this->netTool->getDomainDetailInfo($addr);
        if (count($info) > 0)
            return $info;
        return false;
    }

    /**
     * Checks if IP address have the PTR record in any MX records.
     * Evidently this is correct setting MX and PTR for domain.
     * @param $addr - ip address
     * @return bool
     */
    public function isMailServer($addr)
    {
        if ($info = $this->getDomainInformation($addr)) {
            if (!in_array($info['ptrRecord'], $info['mxRecords']) === false)
                return true;
        }
        return false;
    }

    /**
     * Refresh alive blacklists host names in static file (/blacklistsAlive.txt)
     * @return $this
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    public function updateAliveBlacklistFile()
    {
        $this->fileSys->deleteAliveBlacklist();
        $this->setBlacklists();
        return $this;
    }

    /**
     * Clean blacklist array from previous search
     *
     * @param bool $checkResponse - default TRUE, FALSE is faster but without check DNSBL response
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function cleanBlacklistArray($checkResponse = true)
    {
        $this->dataGrid->cleanPrevResults($checkResponse);
        return $this;
    }

    /**
     * Check IP address on all DNSBL servers
     * @param string $addr
     * @return $this
     * @throws MxToolboxRuntimeException
     */
    public function checkIpAddressOnDnsbl(&$addr)
    {
        $this->netTool->checkAllDnsbl($addr, $this->dataGrid->getTestResultArray());
        return $this;
    }

}
