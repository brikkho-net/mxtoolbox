<?php
namespace MxToolbox;

use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\FileSystem\BlacklistsHostnameFile;

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
class MxToolbox extends AbstractMxToolbox
{


    public function __construct()
    {
        $this->configure();
    }

    /**
     * Configures the current command.
     */
    public function configure()
    {
    }

    /**
     * Load blacklist and create test array
     * @param array $blacklistHostNames - optional (you may use your own blaclist array)
     * @return $this
     * @throws MxToolboxRuntimeException;
     * @throws MxToolboxLogicException;
     */
    public function buildBlacklistHostnamesArray(&$blacklistHostNames = NULL)
    {
        if (is_null($blacklistHostNames)) {
            try {
                $hosts = new BlacklistsHostnameFile();
                $hosts->loadBlacklistsFromFile('blacklistsAlive.txt');
                $this->setTestResultArray($hosts->getBlacklistsHostNames());
                return $this;
            } catch (MxToolboxRuntimeException $e) {
                if ($e->getCode() == 400) {
                    $hosts->loadBlacklistsFromFile('blacklists.txt');
                    $this->setTestResultArray($hosts->getBlacklistsHostNames());
                    $hosts->makeAliveBlacklistFile($this->setDnsblResponse()->getTestResultArray());
                    return $this;
                }
                return $e;
            }
        }
        $this->setTestResultArray($blacklistHostNames);
        return $this;
    }

    /**
     * Get test blacklist array
     * @return array
     * @throws MxToolboxLogicException
     */
    public function &getTestBlacklistsArray()
    {
        if (is_array($this->getTestResultArray()) && count($this->getTestResultArray()) > 0)
            return $this->getTestResultArray();
        throw new MxToolboxLogicException('Array is empty. Call buildBlacklistHostnamesArray() first.');
    }

    //sprintf('The command defined in "%s" cannot have an empty name.', get_class($this))

    /**
     * Check all (use only alive rBLS - fast check!)
     * @param string $addr
     * @return boolean - TRUE if process is done, FALSE on non valid IP address or if the blacklist is not loaded
     */
    public function checkAllDnsbl($addr)
    {
        $this->checkDigPath();
        if ($this->validateIPAddress($addr) && count($this->testResult) > 0) {
            foreach ($this->testResult as &$blackList) {
                if ($this->checkDnsblPtrRecord($addr, $blackList['blHostName'])) {
                    $blackList['blPositive'] = true;
                    $blackList['blPositiveResult'] = $this->getUrlForPositveCheck($addr, $blackList['blHostName']);
                }
            }
            unset($blackList);
            return true;
        }
        $this->testResult = array();
        return false;
    }

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
