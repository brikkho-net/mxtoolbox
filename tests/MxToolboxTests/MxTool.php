<?php
namespace MXTests;

use MxToolbox\MxToolbox;

/**
 * Class MxTestTool
 * @package MXTests
 */
class MxTestTool extends MxToolbox
{
    private $digPath;
    private $dnsResolver;

    /**
     * MxTestTool constructor.
     * @param string|bool $digPath
     * @param string|bool $dnsResolver
     */
    public function __construct($digPath=false,$dnsResolver=false)
    {
        if($digPath)
            $this->digPath = $digPath;
        if($dnsResolver)
            $this->dnsResolver = $dnsResolver;
        parent::__construct();
    }

    /**
     * configure MxToolbox
     * minimum is digPath and dnsResolver
     */
    public function configure()
    {
//        try {
            $this
                ->setDig($this->digPath)
                ->setDnsResolver($this->dnsResolver);
//        } catch (MxToolboxRuntimeException $e) {
//            echo $e->getMessage();
//            exit(1);
//        } catch (MxToolboxLogicException $e) {
//            echo $e->getMessage();
//            exit(1);
//        }
    }

}