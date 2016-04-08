# MXToolbox

[![Latest Stable Version](https://poser.pugx.org/mxtoolbox/mxtoolbox/v/stable)](https://github.com/heximcz/mxtoolbox/releases)
[![Build Status](https://travis-ci.org/heximcz/mxtoolbox.svg?branch=master)](https://travis-ci.org/heximcz/mxtoolbox)
[![Latest Unstable Version](https://poser.pugx.org/mxtoolbox/mxtoolbox/v/unstable)](https://github.com/heximcz/mxtoolbox)
[![License](https://poser.pugx.org/mxtoolbox/mxtoolbox/license)](https://github.com/heximcz/mxtoolbox/blob/master/LICENSE.md)
[![codecov.io](https://codecov.io/github/heximcz/mxtoolbox/coverage.svg?branch=master)](https://codecov.io/github/heximcz/mxtoolbox?branch=master)
[![Code Climate](https://codeclimate.com/github/heximcz/mxtoolbox/badges/gpa.svg)](https://codeclimate.com/github/heximcz/mxtoolbox)

## Prerequisites

- PHP > 5.6.x
- Installed dnsutils (dig)

## Installation / Usage

- 1. Via composer
    
```
    composer require mxtoolbox/mxtoolbox    
```

- 2. Create a composer.json defining your dependencies.

```json
    {
    "require": {
        "mxtoolbox/mxtoolbox": ">=0.0.1"
        }
    }
```

- 3. Example usage:

``` php
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
        ->setDnsResolver('127.0.0.1')
        // load default blacklists for dnsbl check - optional
        ->setBlacklists();

    /**
     * Get test array prepared for check if you need (without any test results)
     */
    //var_dump($this->getBlacklistsArray());

    /**
     * Check IP address on all DNSBL
     */
    $test->checkIpAddressOnDnsbl('8.8.8.8');

    /**
     *  Get the same array but with a check results
     *
     *  Return structure:
     *  []['blHostName'] = dnsbl hostname
     *  []['blPositive'] = true if IP address have the positive check
     *  []['blPositiveResult'] = array() array of a URL addresses if IP address have the positive check
     *  []['blResponse'] = true if DNSBL host name is alive or DNSBL responded during the test
     *  []['blQueryTime'] = false or response time of a last dig query
     */

    var_dump($test->getBlacklistsArray());
    /**
     * Cleaning old results - REQUIRED only in loop before next test
     *  TRUE = check responses for all DNSBL again (default value)
     *  FALSE = only cleaning old results ([blResponse] => true)
     */
    $test->cleanBlacklistArray(false);

} catch (MxToolboxRuntimeException $e) {
    echo $e->getMessage();
} catch (MxToolboxLogicException $e) {
    echo $e->getMessage();
}
```

[More examples](https://github.com/heximcz/mxtoolbox/tree/master/examples)

## License

[MIT](https://github.com/heximcz/mxtoolbox/blob/master/LICENSE.md)
