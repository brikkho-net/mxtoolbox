<?php
/**
 * Quick DIG - python dig multiprocessing
 *
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://dns-tools.best-hosting.cz/
 */

namespace MxToolbox\NetworkTools;

use MxToolbox\Exceptions\MxToolboxRuntimeException;

/**
 * Class QuickDig
 * @package MxToolbox\NetworkTools
 */
class QuickDig
{
    /** @var NetworkTools */
    private $netTool;
    /** @var DigQueryParser object */
    private $digParser;

    /** @var string IP address*/
    private $ipAddress;
    /** @var array reference */
    private $testResult;
    /** @var mixed (json|array)QuickDig output */
    private $digOutput;

    /**
     * QuickDig constructor.
     * @param NetworkTools $netTool
     */
    public function __construct(NetworkTools $netTool)
    {
        $this->netTool = $netTool;
        $this->digParser = new DigQueryParser();
    }

    /**
     * Start dig multiprocessing - fast process how to run multiple dig tasks in the same time
     * @param string $addr
     * @param array $testResult
     * @return $this
     */
    public function getJsonFromDigMultiprocess($addr, &$testResult)
    {
        $this->ipAddress = $addr;
        $this->testResult = &$testResult;
        $this->netTool->ipValidator($this->ipAddress);
        $this->ipAddress = $this->netTool->getIpAddressFromDomainName($this->ipAddress);
        if (!count($this->testResult) > 0)
            throw new MxToolboxRuntimeException(sprintf('Array is empty for dig checks in: %s\%s.', get_class(), __FUNCTION__));
        // prepare domain names only for python script
        $dnsblDomainNames = array();
        foreach ($this->testResult as $item) {
            if ($item['blResponse'])
                $dnsblDomainNames[] = $item['blHostName'];
        }
        // call python script
        $this->digOutput = shell_exec(
            'python ' .
            dirname(__FILE__) .
            DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Python' . DIRECTORY_SEPARATOR . 'quickDig.py ' .
            $this->netTool->reverseIP($this->ipAddress) . ' ' .
            $this->netTool->getDigPath() . ' ' .
            $this->netTool->getRandomDNSResolverIP() . ' ' .
            escapeshellarg(json_encode($dnsblDomainNames))
        );
        // check errors
        if($this->digOutput == 'error')
            throw new MxToolboxRuntimeException('Python multiprocessing script exception!');
        // parse json to array
        if(!empty($this->digOutput) && $this->isJson($this->digOutput)) {
            $this->digOutput = json_decode($this->digOutput);
            return $this;
        }
        throw new MxToolboxRuntimeException('Python digOutput is empty or does not json format.');
    }

    /**
     * Parse data array returned from multiprocessing 
     * @return $this
     */
    public function parseDataFromMultiprocessing()
    {
        if(is_array($this->digOutput) && count($this->digOutput) > 0) {

            foreach ($this->testResult as &$blackList) {
                if ($digOutput = $this->searchDigResult($blackList['blHostName'])) {
                    if ($this->digParser->isNoError($digOutput)) {
                        $blackList['blPositive'] = true;
                        $blackList['blPositiveResult'] = $this->digParser->getPositiveUrlAddresses($digOutput);
                    }
                    $blackList['blQueryTime'] = $this->digParser->getQueryTime($digOutput);
                }
            }
            //var_dump($this->testResult);
            return $this;
        }
        throw new MxToolboxRuntimeException('DigOutput is empty or does not array. Maybe call getJsonFromDigMultiprocess() first.');
    }

    /**
     * Search dig output for a specific dnsbl domain
     * @param string $domainName
     * @return false|string
     */
    private function searchDigResult($domainName) {
        foreach ($this->digOutput as $item) {
            if (($firstLine = strtok($item[0], "\n")) !== false) {
                if ($this->digParser->isDomainNameInString($domainName, $firstLine))
                    return $item[0];
            }
        }
        return false;
    }

    /**
     * is json
     * @param string $string
     * @return bool
     */
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
}
