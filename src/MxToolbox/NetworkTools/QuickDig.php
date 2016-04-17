<?php
/**
 * Quick DIG - python multiprocessing
 *
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 */

namespace MxToolbox\NetworkTools;

use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;

/**
 * Class QuickDig
 * @package MxToolbox\NetworkTools
 */
class QuickDig
{
    /** @var NetworkTools */
    private $netTool;
    /** @var string */
    private $addr;
    /** @var array */
    private $testResult;
    /** @var string QuickDig output */
    private $digOutput;
    /** @var array */
    private $dnsblDomainNames;

    /**
     * QuickDig constructor.
     * @param string $addr
     * @param array $testResult
     * @param NetworkTools $netTool
     */
    public function __construct($addr, &$testResult, NetworkTools $netTool)
    {
        $this->addr = $addr;
        $this->testResult = $testResult;
        $this->netTool = $netTool;
    }

    /**
     * Start dig multiprocessing - fast process how to run multiple dig tasks in the same time
     * @return $this
     */
    public function getJsonFromDigMultiprocess()
    {
        foreach ($this->testResult as $item) {
            if ($item['blResponse'])
                $this->dnsblDomainNames[] = $item['blHostName'];
        }
        $this->netTool->ipValidator($this->addr);
        $this->digOutput = shell_exec(
            'python ' .
            dirname(__FILE__) .
            DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Python' . DIRECTORY_SEPARATOR . 'quickDig.py ' .
            $this->netTool->reverseIP($this->addr) . ' ' .
            $this->netTool->getDigPath() . ' ' .
            $this->netTool->getRandomDNSResolverIP() . ' ' .
            escapeshellarg(json_encode($this->dnsblDomainNames))
        );
        if($this->digOutput == 'error')
            throw new MxToolboxRuntimeException('Python exception!');
        return $this;
    }

    public function parseJsonDataFromPython()
    {
        $parser = new DigQueryParser();
        if(!empty($this->digOutput) && $this->isJson($this->digOutput)) {
            $this->digOutput = json_decode($this->digOutput);
            //TODO: parse response
            var_dump($this->digOutput);
            exit();
        }
        
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
    
    /*
     *             foreach ($testResult as &$blackList) {
                $digOutput = $this->getDigResult(
                    $this->ipAddress,
                    $this->getRandomDNSResolverIP(),
                    $blackList['blHostName'], 'TXT'
                );

                if ($this->digParser->isNoError($digOutput)) {
                    $blackList['blPositive'] = true;
                    $blackList['blPositiveResult'] = $this->digParser->getPositiveUrlAddresses($digOutput);
                }

                $blackList['blQueryTime'] = $this->digParser->getQueryTime($digOutput);
            }
            return $this;

     */
}