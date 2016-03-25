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

/**
 * Class easyTest
 */
class easyTest extends MxToolbox
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
            ->setDnsResolver('127.0.0.1')
            // load default blacklists (from the file: blacklistAlive.txt)
            ->setBlacklists();
    }

    /**
     * Test IP address
     * @param string $addr
     */
    public function testMyIPAddress($addr)
    {

        try {
             // Checks IP address on all DNSBL
            $this->checkIpAddressOnDnsbl($addr);
             /*
             * getBlacklistsArray() structure:
             * []['blHostName'] = dnsbl hostname
             * []['blPositive'] = true if IP address have the positive check
             * []['blPositiveResult'] = array() array of a URL addresses if IP address have the positive check
             * []['blResponse'] = true if DNSBL host name is alive and send test response before test
             * []['blQueryTime'] = false or response time of a last dig query
             */
            var_dump($this->getBlacklistsArray());
        } catch (MxToolboxRuntimeException $e) {
            echo $e->getMessage();
        } catch (MxToolboxLogicException $e) {
            echo $e->getMessage();
        }
    }

}

$test = new easyTest($myBlacklist);
$test->testMyIPAddress('8.8.8.8');
```

[More examples](https://github.com/heximcz/mxtoolbox/tree/master/examples)

## License

[MIT](https://github.com/heximcz/mxtoolbox/blob/master/LICENSE.md)
