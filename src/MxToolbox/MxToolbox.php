<?php
/**
 * DNSBL tool
 *
 * test your IP address on very known spam databases and blacklists
 *
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 * @version 0.1.0-dev
 *
 */
namespace MxToolbox;

use MxToolbox\Container\MxToolboxContainer;
use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;

/**
 * Class MxToolbox
 * @package MxToolbox
 */
class MxToolbox extends MxToolboxContainer
{
    /** @var \MxToolbox\FileSystem\BlacklistsHostnameFile service */
    private $fileSys;
    /** @var \MxToolbox\DataGrid\MxToolboxDataGrid service */
    private $dataGrid;
    /** @var \MxToolbox\NetworkTools\NetworkTools service */
    private $netTool;

    /**
     * MxToolbox constructor.
     * @throws MxToolboxLogicException
     * @throws MxToolboxRuntimeException
     */
    public function __construct()
    {
        $this->netTool = $this->createServiceNetworkTool();
        $this->fileSys = $this->createServiceBlacklistsHostnameFile();
        $this->dataGrid = $this->createServiceMxToolboxDataGrid();
    }

     /**
     * Set dig path - required, etc: /usr/bin/dig
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
     * Set path to the blacklist files - optional
     * @param string $path
     * @return $this
     */
    public function setBlacklistFilePath($path) {
        $this->fileSys->setBlacklistFilePath($path);
        return $this;
    }
    
    /**
     * Set DNS resolver IP address - required (support for multiples push)
     * @param string $addr IP address
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function setDnsResolver($addr)
    {
        $this->netTool->setDnsResolverIP($addr);
        return $this;
    }

    /**
     * Initialize blacklist array from file or custom array - optional
     * @param array $ownBlacklist optional, default nothing
     * @return $this
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    public function setBlacklists($ownBlacklist = null)
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
     *      
     *      array( array(
     *      ['blHostName'] => string '<dnsbl hostname>',
     *      ['blPositive'] => boolean <true if IP address have the positive check for blHostName>,
     *      ['blPositiveResult'] => array() <array of a URL addresses if IP address have the positive check>,
     *      ['blResponse'] => boolean <true if DNSBL host name is alive and send test response before test>,
     *      ['blQueryTime'] => boolean <false or response time of a last dig query>
     *      ));
     * @return array
     * @throws MxToolboxLogicException
     */
    public function getBlacklistsArray()
    {
        return $this->dataGrid->getTestResultArray();
    }

    /**
     * Get some additional information about IP address (IP Address, PTR record, Domain name, MX records )
     *  
     *  Return information in array(
     *      ['ipAddress'] => string,
     *      ['domainName'] => string,
     *      ['ptrRecord'] => string,
     *      ['mxRecords'] => array(
     *  ));
     * @param string $addr IP address or domain name
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
     * Get SMTP diagnostics information.
     *  
     *  Return information in array(
     *      ['rDnsMismatch']['state'] => boolean,
     *      ['rDnsMismatch']['info'] => string,
     *      ['validHostname']['state'] => boolean,
     *      ['validHostname']['info'] => string,
     *      ['bannerCheck']['state'] => boolean,
     *      ['bannerCheck']['info'] => string,
     *      ['tls']['state'] => boolean,
     *      ['tls']['info'] => string,
     *      ['openRelay']['state'] => boolean,
     *      ['openRelay']['info'] => string,
     *      ['errors']['state'] => boolean,
     *      ['errors']['info'] => string,
     *      ['allResponses'] => array(
     *          [connection] => string,
     *          [ehlo] => array(),
     *          [mailFrom] => string,
     *          [rcptTo] => string,
     *          ),
     *  );
     *
     * @param string $addr IP address or domain name
     * @param string $myHostName real HostName of the server where script is running (must be resolved to IP address)
     * @param string $mailFrom Any testing mail address (domain is same as hostname)
     * @param string $mailRcptTo non exist email address as test@example.com
     * @return array
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    public function getSmtpDiagnosticsInfo($addr, $myHostName, $mailFrom, $mailRcptTo)
    {
        return $this
            ->createServiceSmtpServerChecks($addr, $myHostName, $mailFrom, $mailRcptTo)
            ->getSmtpServerDiagnostic();
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
     * Check IP address or domain name on all DNSBL servers
     * @param string $addr ip address or domain name
     * @param boolean $quick (default false) for run quick multiprocess Python >=2.7 required
     * @return $this
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    public function checkIpAddressOnDnsbl($addr, $quick = false)
    {
        if($quick) {
            $quickDig = $this->createServiceQuickDig($addr, $this->dataGrid->getTestResultArray());
            $quickDig
                ->getJsonFromDigMultiprocess()
                ->parseJsonDataFromPython();
            return $this;
        }
        $this->netTool->checkAllDnsbl($addr, $this->dataGrid->getTestResultArray());
        return $this;
    }

}
