<?php
/**
 * Class for arrays and other data
 * 
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 */
namespace MxToolbox\DataGrid;

use MxToolbox\NetworkTools\NetworkTools;
use MxToolbox\FileSystem\BlacklistsHostnameFile;
use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;

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
     * []['blQueryTime'] = false or response time of a last dig query
     *
     * @var array for dnsbl tests
     */
    protected $testResultStructure;
    /** @var NetworkTools object */
    private $netTool;
    /** @var BlacklistsHostnameFile object */
    private $fileSys;

    /**
     * MxToolboxDataGrid constructor.
     * @param BlacklistsHostnameFile $fileSys
     * @param NetworkTools $netTool
     */
    public function __construct(BlacklistsHostnameFile &$fileSys, NetworkTools &$netTool)
    {
        if ($fileSys instanceof BlacklistsHostnameFile)
            $this->fileSys = $fileSys;
        if ($netTool instanceof NetworkTools)
            $this->netTool = $netTool;
    }

    /**
     * Return complete array with tests
     * @return array
     * @throws MxToolboxLogicException
     */
    public function &getTestResultArray()
    {
        if ($this->isArrayInitialized($this->testResultStructure))
            return $this->testResultStructure;
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
        // build user own blacklist array
        $this->setTestResultArray($blacklistHostNames, false, true);
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
     * @param bool $alive TRUE is set default blResponse (usually load from alive file)
     * @param bool $ownBlacklist
     * @return $this
     * @throws MxToolboxLogicException
     */
    protected function setTestResultArray(&$blacklistHostNamesArray, $alive = true, $ownBlacklist = false)
    {
        if ($this->isArrayInitialized($blacklistHostNamesArray)) {
            $this->testResultStructure = array();
            foreach ($blacklistHostNamesArray as $index => &$blackList) {
                $this->testResultStructure[$index]['blHostName'] = $blackList;
                $this->testResultStructure[$index]['blPositive'] = false;
                $this->testResultStructure[$index]['blPositiveResult'] = array();
                $this->testResultStructure[$index]['blResponse'] = $alive;
                if ($ownBlacklist)
                    $this->testResultStructure[$index]['blResponse'] = $this->netTool->isDnsblResponse($blackList);
                $this->testResultStructure[$index]['blQueryTime'] = false;
            }
            unset($blackList);
            return $this;
        }
        throw new MxToolboxLogicException(sprintf('Input parameter is empty or is not a array in %s\%s()', get_class(), __FUNCTION__));
    }

    /**
     * Clean previous results, reinitialize array
     * @param bool $checkResponse - FALSE is faster but without check DNSBL response
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function cleanPrevResults($checkResponse = true)
    {
        if ($this->isArrayInitialized($this->testResultStructure)) {
            foreach ($this->testResultStructure as $index => &$blackList) {
                // here is default true because blacklist is loaded from alive file 
                $this->testResultStructure[$index]['blResponse'] = true;
                if ($checkResponse)
                    $this->testResultStructure[$index]['blResponse'] = $this->netTool->isDnsblResponse($this->testResultStructure[$index]['blHostName']);
                $this->testResultStructure[$index]['blPositive'] = false;
                $this->testResultStructure[$index]['blPositiveResult'] = array();
                $this->testResultStructure[$index]['blQueryTime'] = false;
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