<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

/**
 * Class refreshDnsblFile
 */
class refreshDnsblFile extends MxToolbox
{
    /**
     * Configure MXToolbox
     * configure() is abstract function and must by implemented
     */
    protected function configure()
    {
        $this
            // path to the dig tool
            ->setDig('/usr/bin/dig')
            
            // multiple resolvers is allowed
            //->setDnsResolver('8.8.8.8')
            //->setDnsResolver('8.8.4.4')
            ->setDnsResolver('127.0.0.1');
    }

    /**
     * Refresh DNSBL file
     */
    public function testMyIPAddress()
    {

        try {

            // update the blacklistAlive.txt file
            $this->updateAliveBlacklistFile();

            // get array with dns resolvers
            var_dump($this->getBlacklistsArray());

        } catch (MxToolboxRuntimeException $e) {
            echo $e->getMessage();
        } catch (MxToolboxLogicException $e) {
            echo $e->getMessage();
        }
    }

}

$test = new refreshDnsblFile($myBlacklist);
$test->testMyIPAddress();
