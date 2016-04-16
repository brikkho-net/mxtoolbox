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
    /** @var string json */
    private $jsonData;
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
        $this->jsonData = shell_exec(
            'python ' .
            dirname(__FILE__) .
            DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Python' . DIRECTORY_SEPARATOR . 'quickDig.py ' .
            $this->netTool->reverseIP($this->addr) . ' ' .
            $this->netTool->getDigPath() . ' ' .
            $this->netTool->getRandomDNSResolverIP() . ' ' .
            escapeshellarg(json_encode($this->dnsblDomainNames))
        );
        if ($this->jsonData == 'error')
            throw new MxToolboxRuntimeException('Python exception!');
        return $this;
    }

    public function parseJsonDataFromPython()
    {
        $parser = new DigQueryParser();
        
    }

}