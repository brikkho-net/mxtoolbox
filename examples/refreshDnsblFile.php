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
    public function configure()
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
     * Refresh file with alive blacklists
     */
    public function refreshAliveBlacklistsFile()
    {

        /*
         * Update the blacklistAlive.txt file - ! time-consuming process !
         */
        $this->updateAliveBlacklistFile();

        /*
         * Get new test array prepared for check (without any test results)
         */
        var_dump($this->getBlacklistsArray());

    }

}

try {
    $test = new refreshDnsblFile();
    $test->refreshAliveBlacklistsFile();
} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}