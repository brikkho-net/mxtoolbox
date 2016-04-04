<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

/**
 * Class getAdditionalInformation
 */
class getAdditionalInformation extends MxToolbox
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
            ->setDnsResolver('127.0.0.1');
    }

    /**
     * Get information about IP address or domain name
     * @param string $addr
     */
    public function getInfo($addr)
    {

        /* Get additional information for IP address
         * Array structure:
         * ['domainName']
         * ['ptrRecord']
         * ['mxRecords'][array]
         */
        var_dump($this->getDomainInformation($addr));


    }

}

try {
    $test = new getAdditionalInformation();
    $test->getInfo('8.8.8.8');
} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}