<?php
namespace MxToolbox\DataGrid;

use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\FileSystem\BlacklistsHostnameFile;
use MxToolbox\NetworkTools\NetworkTools;

/**
 * Class MxToolboxDataGrid
 * @package MxToolbox\DataGrid
 */
class MxToolboxDataGrid
{

    /**
     * Structure:
     * []['blHostName'] = dnsbl hostname
     * []['blPositive'] = true if IP addres have the positive check
     * []['blPositiveResult'] = array() array of a URL addresses if IP address have the positive check
     * []['blResponse'] = true if DNSBL host name is alive and send test response before test
     * []['blQueryTime'] = false or response time of a last query
     *
     * @var array for dnsbl tests
     */
    protected $testResult;
    /** @var NetworkTools */
    private $netTool;
    /** @var BlacklistsHostnameFile */
    private $fileSys;

    /**
     * Get test results array
     * @return array
     * @throws MxToolboxLogicException
     */
    public function __construct(BlacklistsHostnameFile &$fileSys, NetworkTools &$netTool)
    {
        if ($fileSys instanceof BlacklistsHostnameFile)
            $this->fileSys = $fileSys;
        if ($netTool instanceof NetworkTools)
            $this->netTool = $netTool;
    }

    public function &getTestResultArray()
    {
        if ($this->isArrayInitialized($this->testResult))
            return $this->testResult;
        throw new MxToolboxLogicException(sprintf('Array is empty in %s\%s(), set first test results array.', get_class(), __FUNCTION__));
    }

    /**
     * Load alive blacklists and create test array.
     * @see BlacklistsHostnameFile::loadBlacklistsFromFile()
     * @param array $blacklistHostNames - optional (you may use your own blacklist array, default NULL)
     * @return $this
     * @throws MxToolboxRuntimeException;
     * @throws MxToolboxLogicException;
     */
    public function buildBlacklistHostNamesArray(&$blacklistHostNames = NULL)
    {
        if (is_null($blacklistHostNames) || !is_array($blacklistHostNames)) {
            $this->fileSys->loadBlacklistsFromFile('blacklistsAlive.txt');
            $this->setTestResultArray($this->fileSys->getBlacklistsHostNames(), true);
            return $this;
        }
        $this->setTestResultArray($blacklistHostNames, true);
        return $this;
    }

    /**
     * Load default blacklist and check all for alive
     * @see BlacklistsHostnameFile::makeAliveBlacklistFile
     * @return $this
     * @throws MxToolboxLogicException
     * @throws MxToolboxRuntimeException
     */
    public function buildNewBlacklistHostNames()
    {
        $this->fileSys->loadBlacklistsFromFile('blacklists.txt');
        // set default blacklists to $testResults
        $this->setTestResultArray($this->fileSys->getBlacklistsHostNames(), false);
        // set only alive host names
        $this->netTool->setDnsblResponse($this->getTestResultArray());
        // check all for alive
        $this->fileSys->makeAliveBlacklistFile($this->getTestResultArray());
        // build array from blacklistAlive file
        $this->buildBlacklistHostNamesArray();
        return $this;
    }

    /**
     * Build the array for check DNSBLs
     * @param array $blacklistHostNamesArray
     * @param boolean true is alive
     * @return $this
     * @throws MxToolboxLogicException
     */
    protected function setTestResultArray(&$blacklistHostNamesArray, $alive)
    {
        if ($this->isArrayInitialized($blacklistHostNamesArray)) {
            $this->testResult = array();
            foreach ($blacklistHostNamesArray as $index => &$blackList) {
                $this->testResult[$index]['blHostName'] = $blackList;
                $this->testResult[$index]['blPositive'] = false;
                $this->testResult[$index]['blPositiveResult'] = array();
                $this->testResult[$index]['blResponse'] = $alive;
                $this->testResult[$index]['blQueryTime'] = false;
            }
            unset($blackList);
            return $this;
        }
        throw new MxToolboxLogicException(sprintf('Input parameter is empty or is not a array in %s\%s()', get_class(), __FUNCTION__));
    }

    /**
     * Clean previous results, reinitialize array
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function cleanPrevResults()
    {
        if ($this->isArrayInitialized($this->testResult)) {
            foreach ($this->testResult as $index => &$blackList) {
                $this->testResult[$index]['blResponse'] = $this->netTool->isDnsblResponse($this->testResult[$index]['blHostName']);
                $this->testResult[$index]['blPositive'] = false;
                $this->testResult[$index]['blPositiveResult'] = array();
                $this->testResult[$index]['blQueryTime'] = false;
            }
            unset($blackList);
            return $this;
        }
        throw new MxToolboxLogicException(sprintf('Array is empty in %s\%s(), set first test results array.', get_class(), __FUNCTION__));
    }

    /**
     * Simple check if var is a array and non empty
     * @param array $anyArray
     * @return bool
     */
    protected function isArrayInitialized(&$anyArray)
    {
        if (is_array($anyArray) && count($anyArray) > 0)
            return true;
        return false;
    }
}