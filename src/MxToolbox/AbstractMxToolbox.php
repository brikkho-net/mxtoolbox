<?php

namespace MxToolbox;

use MxToolbox\Exception\MxToolboxException;

abstract class AbstractMxToolbox {
	
	/**
	 * @var PTR record from the method checkExistPTR()
	 */
	protected $recordPTR;
	/**
	 * @var domain name from the method checkExistPTR()
	 */
	protected $domainName;
	/**
	 * @var DNSBL array 
	 */
	protected $blackLists;
	/**
	 * @var DNSBL test results
	 */
	protected $testResult;
	/**
	 * @var whereis dig
	 */
	protected $digPath;
	/**
	 * @var DNS resolvers
	 */
	protected $resolvers;
	
	protected function getUrlForPositveCheck($addr,$blackList) {
		$rIP = $this->reverseIP($addr);
		$checkResult = shell_exec( $this->digPath . ' @' . $this->getRandomDNSResolverIP() . ' +time=3 +tries=1 +noall +answer '.$rIP . '.' . $blackList.' TXT');
		$txtResult = explode(PHP_EOL, trim($checkResult));
		$matches = array();
		$urlAddress = array();
		foreach ($txtResult as $line) {
			if ( preg_match("/((\w+:\/\/)[-a-zA-Z0-9:@;?&=\/%\+\.\*!'\(\),\$_\{\}\^~\[\]`#|]+)/", $line, $matches) )
				$urlAddress[] = $matches[1];
		}
		return $urlAddress;
	}

	protected function checkOnerBLSARecord($addr,$blackList) {
		$rIP = $this->reverseIP($addr);
		// dig @194.8.253.11 -4 +noall +answer +stats 2.0.0.127.xbl.spamhaus.org A
		// TODO: +stats , parse query time
		// TODO: -4
		
		$checkResult = shell_exec($this->digPath . ' @' . $this->getRandomDNSResolverIP() . ' +time=3 +tries=1 +noall +answer '.$rIP . '.' . $blackList.' A');
		if ( !empty($checkResult) )
				return true;
		return false;
	}

	/**
	 * Build the array for check od DNSBL
	 */
	protected function buildTestArray() {
		$this->testResult = array();
		$this->checkDigPath();
		$index = 0;
		foreach ($this->blackLists as $blackList) {
			$this->testResult[$index]['blHostName'] = $blackList;
			$this->testResult[$index]['blPositive'] = false;
			$this->testResult[$index]['blPositiveResult'] = array();
			$this->testResult[$index]['blResponse'] = false;
			// TODO: add query time
			// https://tools.ietf.org/html/rfc5782 cap. 5
			if ( $this->checkOnerBLSARecord('127.0.0.2', $blackList) )
				$this->testResult[$index]['blResponse'] = true;

			$index++;
		}
	}

	/**
	 * Reverse IP address 192.168.1.254 -> 254.1.168.192
	 * @param string $addr
	 * @return string
	 */
	protected function reverseIP($addr) {
		$revIpAddr = explode( ".", $addr );
		return $revIpAddr[3] . '.' . $revIpAddr[2] . '.' . $revIpAddr[1] . '.' . $revIpAddr[0];
	}

	/**
	 * Load blacklists from the file $fileName to array
	 * @param string $fileName
	 * @throws MxToolboxException;
	 * @return mixed throw|boolean
	 */
	protected function loadBlacklistsFromFile($fileName) {
		$this->blackLists = array();
		$blFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . $fileName;
		if ( !is_readable( $blFile ) )
			return false;
		if ( ! ( $tmpBlacklists = file_get_contents( $blFile ) ) === false ) {
			$tmpBlacklists = explode( PHP_EOL, $tmpBlacklists );
			$tmpBlacklists = array_filter( $tmpBlacklists, array( $this, "checkHostName" ));
			foreach ( $tmpBlacklists as $blackList )
				$this->blackLists[] = trim($blackList);
			if ( ! count($this->blackLists) > 0 ) 
				throw new MxToolboxException("Blacklist is empty: " . $blFile);
			return true;
		}
		return false;
	}

	protected function checkHostName($hostName) {
		$validHostnameRegex = "/^[a-zA-Z0-9.\-]{2,256}\.[a-z]{2,6}$/";
		if ( preg_match( $validHostnameRegex, trim($hostName) ) )
			return true;
		return false;
	}
	
	protected function getRandomDNSResolverIP() {
		if ( ! count($this->resolvers) > 0 )
			throw new MxToolboxException('No DNS resolver here!');
		return $this->resolvers[array_rand($this->resolvers, 1)];
	}

}
