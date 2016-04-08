<?php
use MxToolbox\MxToolbox;
use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../src/MxToolbox/autoload.php';

try {

    $test = new MxToolbox();

    /**
     * Configure MxToolbox
     */
    $test
        // path to the dig tool - required
        ->setDig('/usr/bin/dig')
        // set dns resolver - required
        //->setDnsResolver('8.8.8.8')
        //->setDnsResolver('8.8.4.4')
        ->setDnsResolver('127.0.0.1');

    /**
     * Get SMTP server diagnostics responses
     */
    var_dump($test->getSmtpDiagnosticsInfo(
        '64.12.91.197',
        'google.com',
        'mxtool@example.com',
        'test@example.com'
    ));

} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage().PHP_EOL;
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage().PHP_EOL;
}
