<?php
namespace MxToolbox\DataGrid;

use MxToolbox\Exceptions\MxToolboxLogicException;
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
    /** @var BlacklistsHostnameFile  */
    private $fileSys;

    /**
     * Get test results array
     * @return array
     * @throws MxToolboxLogicException
     */
    public function __construct(BlacklistsHostnameFile &$fileSys, NetworkTools &$netTool)
    {
        if($fileSys instanceof BlacklistsHostnameFile)
            $this->fileSys = $fileSys;
        if($netTool instanceof NetworkTools)
            $this->netTool = $netTool;
   }

    public function &getTestResultArray()
    {
        if ($this->isArrayInitialized($this->testResult))
            return $this->testResult;
        throw new MxToolboxLogicException(sprintf('Array is empty in %s\%s(), set first test results array.', get_class(), __FUNCTION__));
    }

    /**
     * Load blacklist and create test array.
     * @param array $blacklistHostNames - optional (you may use your own blacklist array, default NULL)
     * @return $this
     * @throws MxToolboxRuntimeException;
     * @throws MxToolboxLogicException;
     */
    public function buildBlacklistHostnamesArray(&$blacklistHostNames = NULL)
    {
        if (is_null($blacklistHostNames) || !is_array($blacklistHostNames)) {
            $this->fileSys->loadBlacklistsFromFile('blacklistsAlive.txt');
            $this->setTestResultArray($this->fileSys->getBlacklistsHostNames(), true);
            return $this;
        }
        $this->setTestResultArray($blacklistHostNames, true);
        return $this;
    }

    public function buildNewBlacklistHostNames()
    {
        $this->fileSys->loadBlacklistsFromFile('blacklists.txt');
        $this->setTestResultArray($this->fileSys->getBlacklistsHostNames(), false);
        
        if ($this->isArrayInitialized($this->getTestResultArray())) {
            $this->fileSys->makeAliveBlacklistFile(
                $this->getTestResultArray(
                    $this->netTool->setDnsblResponse($this->getTestResultArray())
                )
            );
            $this->buildBlacklistHostnamesArray($this->fileSys->getBlacklistsHostNames());
            return $this;
        }
        throw new MxToolboxLogicException(sprintf('Array is empty in %s\%s()', get_class(), __FUNCTION__));
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
    protected function cleanPrevResults()
    {
        if ($this->isArrayInitialized($this->testResult)) {
            foreach ($this->testResult as $index => &$blackList) {
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
     * Simple check if any array is initialized and non empty
     * @param array
     * @return bool
     */
    protected function isArrayInitialized(&$anyArray)
    {
        if (is_array($anyArray) && count($anyArray) > 0)
            return true;
        return false;
    }
}