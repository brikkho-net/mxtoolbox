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

```php
<?php

use MxToolbox\MxToolbox;
use MxToolbox\Exception\MxToolboxException;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'vendor/autoload.php';

try {
	/**
	 * IP address for test
	 */
	$addr = '';
	/**
	 * Create MxToolbox object
	 */
	$mxt = new MxToolbox('/usr/bin/dig');
	/**
	 * Push one or more IP address of your DNS resolvers
	 */
	$mxt->pushDNSResolverIP('127.0.0.1');
	$mxt->pushDNSResolverIP('127.0.1.1');
	/**
	 * Load blacklist
	 */
	$mxt->loadBlacklist();
	/**
	 * check IP address
	 */
	$mxt->checkAllrBLS($addr);
	/**
	 * Show results.
	 * Structure:
	 * []['blHostName'] = DNSBL host name
	 * []['blPositive'] = true if IP addres have the positive check
	 * []['blPositiveResult'][] = array of URL address if IP address have the positive chech
	 * []['blResponse'] = true if DNSBL host name is alive and send test response before test
	 */
	var_dump($mxt->getCheckResult());

} catch ( MxToolboxException $e ) {
	echo 'Caught exception: ',  $e->getMessage(), PHP_EOL;
}
```

[More examples](https://github.com/heximcz/mxtoolbox/tree/master/examples)

## License

[MIT](https://github.com/heximcz/mxtoolbox/blob/master/LICENSE.md)
