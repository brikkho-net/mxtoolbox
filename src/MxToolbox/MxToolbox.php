<?php
namespace MxToolbox;

use MxToolbox\NetworkTools\NetworkTools;
use MxToolbox\DataGrid\MxToolboxDataGrid;
use MxToolbox\FileSystem\BlacklistsHostnameFile;
use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;

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
    protected function setDig($digPath)
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
    protected function setDnsResolver($addr)
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
    protected function setBlacklists(&$ownBlacklist = null)
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
    protected function getDnsResolvers()
    {
        return $this->netTool->getDnsResolvers();
    }

    /**
     * Get DIG path
     * @return string
     */
    protected function getDigPath()
    {
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
     * Get some additional information about IP address (PTR record, Domain name, MX records )
     * Array structure: ['domainName'],['ptrRecord'],['mxRecords'][array]
     * @param $addr - ip address
     * @return array|bool - return array or FALSE if no information here.
     */
    protected function getDomainInformation($addr) {
        $info = $this->netTool->getDomainDetailInfo($addr);
        if (count($info) > 0)
            return $info;
        return false;
    }

    /**
     * Refresh alive blacklists host names in static file (/blacklistsAlive.txt)
     * @return $this
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    protected function updateAliveBlacklistFile()
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
    protected function cleanBlacklistArray($checkResponse = true)
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
    protected function checkIpAddressOnDnsbl(&$addr)
    {
        $this->netTool->checkAllDnsbl($addr, $this->dataGrid->getTestResultArray());
        return $this;
    }

}
