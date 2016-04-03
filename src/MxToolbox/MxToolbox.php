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
 * @version 0.0.3
 */
namespace MxToolbox;

use MxToolbox\NetworkTools\NetworkTools;
use MxToolbox\DataGrid\MxToolboxDataGrid;
use MxToolbox\FileSystem\BlacklistsHostnameFile;
use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\NetworkTools\SmtpServerChecks;

/**
 * Class MxToolbox
 * @package MxToolbox
 */
abstract class MxToolbox
{
    /** @var BlacklistsHostnameFile object */
    private $fileSys;
    /** @var MxToolboxDataGrid object */
    private $dataGrid;
    /** @var NetworkTools object */
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
     * Initialize blacklist array fom file or custom array
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
    public function &getBlacklistsArray()
    {
        return $this->dataGrid->getTestResultArray();
    }

    /**
     * Get some additional information about IP address (PTR record, Domain name, MX records )
     * Array structure: ['domainName'],['ptrRecord'],['mxRecords'][array]
     * @param string $addr - ip address
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
     * @param string $addr IP address or hostname for test
     * @param string $myHostName real HostName of the server where script is running (must be resolved to IP address)
     * @param string $mailFrom Any testing mail address (domain is same as hostname)
     * @param string $mailRcptTo non exist email address as test@example.com
     * @return array
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    public function getSmtpDiagnosticsInfo($addr, $myHostName, $mailFrom, $mailRcptTo)
    {
        $smtp = new SmtpServerChecks($this->netTool, $addr, $myHostName, $mailFrom, $mailRcptTo);
        return $smtp->getSmtpServerDiagnostic();
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
