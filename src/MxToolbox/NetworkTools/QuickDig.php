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


class QuickDig
{
    /** @var NetworkTools */
    private $netTool;
    /** @var string */
    private $addr;
    /** @var array */
    private $testResult;
    
    public function __construct($addr, &$testResult, $netTool)
    {
        $this->netTool = $netTool;
        $this->addr = $addr;
        $this->testResult = $testResult;
    }
    
    public function digMultiprocess(){
       // $jsonData = shell_exec('python ./quickDig.py 2.0.0.127 /usr/bin/dig 127.0.0.1 /<path>/blacklistsAlive.txt');
    }
}