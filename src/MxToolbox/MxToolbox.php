<?php
/**
 * MxToolBox
 * @version 0.0.1
 */
namespace MxToolbox;

use MxToolbox\Exception\MxToolboxException;

class MxToolbox {
	
	/**
	 * @var PTR record from the method checkExistPTR()
	 */
	private $recordPTR;
	/**
	 * @var domain name from the method checkExistPTR()
	 */
	private $domainName;
	/**
	 * @var DNSBL array 
	 */
	private $blackLists;
	/**
	 * @var DNSBL test results
	 */
	private $testResult;
	/**
	 * @var whereis dig
	 */
	private $digPath;
	/**
	 * @var DNS resolvers
	 */
	private $resolvers;
	
	/**
	 * MxToolbox
	 * @param string $digPath - whereis dig
	 * @throws MxToolboxException
	 */
	public function __construct($digPath = '') {
		if ($digPath!='') {
			if ( !file_exists($digPath) )
				throw new MxToolboxException('DIG path: ' . $digPath . ' File does not exist!');
			$this->digPath = $digPath;
		}
	}
	
	public function loadBlacklist() {
		if ( !$this->loadBlacklistsFromFile('blacklistsAlive.txt') ) {
			$this->makeAliveBlacklistFile();
		}
		$this->buildTestArray();
	}
	
	/**
	 * Get blacklists hostnames
	 * @return array
	 */
	public function getBlackLists() {
		return $this->blackLists;
	}
	
	/**
	 * Get test results
	 * @return mixed array|bool
	 */
	public function getCheckResult() {
		if ( count($this->testResult) > 0 )
			return $this->testResult;
		return false;
	}
	
	/**
	 * Get PTR record
	 * @return mixed string|bool
	 */
	public function getPTR() {
		if ( !empty($this->recordPTR) )
			return $this->recordPTR;
		return false;
	}
	
	/**
	 * Get domain name
	 * @return mixed string|bool
	 */
	public function getDomainName() {
		if ( !empty($this->domainName) )
			return $this->domainName;
		return false;
	}

	/**
	 * Get MX records from domain name
	 * @param string $hostName
	 * @return mixed - ARRAY with MX records or FALSE
	 */
	public function getMXRecords($hostName) {
		if ( $this->checkHostName($hostName) ) {
			$ptr = dns_get_record( $hostName, DNS_MX );
			if ( isset($ptr[0]['target']) ) {
				$MXRecords = array();
				foreach ($ptr as $mx)
					$MXRecords[] = $mx['target'];
				return $MXRecords;
			}
		}
		return false;
	}

	/**
	 * Check if IP address have a PTR record
	 * @param string $addr
	 * @return boolean
	 */
	public function checkExistPTR($addr) {
		$this->recordPTR = '';
		$this->domainName = '';
		if ( ! $this->validateIPAddress($addr) )
			return false;
		$ptr = dns_get_record( $this->reverseIP($addr) . '.in-addr.arpa.', DNS_PTR );
		if ( isset($ptr[0]['target']) ) {
			$regs = array();
			$this->recordPTR = $ptr[0]['target'];
			if ( preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $ptr[0]['target'], $regs) )
				$this->domainName = $regs['domain'];
			return true;
		}
		return false;
	}
	
	/**
	 * Push IP address of a DNS resolver to the reslovers list
	 * (udp port 53 must be open)
	 * @param string $addr
	 * @return boolean
	 */
	public function pushDNSResolverIP($addr) {
		$errno = ''; $errstr = '';
		if ( $this->validateIPAddress($addr) && $fss = @fsockopen( 'udp://'.$addr, 53, $errno, $errstr, 5 )) {
			fclose($fss);
			$this->resolvers[] = $addr;
			return true;
		}
		return false;
	}

	/**
	 * Validate if string is valid IP address
	 * @param string $addr
	 * @return boolean
	 */
	public function validateIPAddress($addr) {
		return filter_var ( $addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}
	
	/**
	 * Check all (use only alive rBLS - fast check!)
	 * @param string $addr
	 * @return boolean - TRUE if process is done, FALSE on non valid IP address or if the blacklist is not loaded 
	 */
	public function checkAllrBLS($addr) {
		if ( ! file_exists($this->digPath) )
			throw new MxToolboxException('DIG path: ' . $this->digPath . ' File does not exist!');
		
		if ( $this->validateIPAddress($addr) && count($this->testResult) > 0 ) {
			foreach ($this->testResult as &$blackList) {
				if ( $this->checkOnerBLSARecord($addr, $blackList['blHostName']) ) {
					$blackList['blPositive'] = true;
					$blackList['blPositiveResult'] = $this->getUrlForPositveCheck($addr, $blackList['blHostName']);
				}
			}
			unset($blackList);
			return true;
		}
		$this->testResult = array();
		return false;
	}

	/**
	 * Build new file with alive DNSBLs hostnames
	 * (ideal for frequent testing)
	 * @return boolean
	 */
	public function makeAliveBlacklistFile() {
		if ( ! file_exists($this->digPath) )
			throw new MxToolboxException('DIG path: ' . $this->digPath . ' File does not exist!');
		$blAlivePath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
		$blAliveFileTmp = $blAlivePath . 'blacklistsAlive.tmp';
		$blAliveFileOrg = $blAlivePath . 'blacklistsAlive.txt';
		// create temp file
		if (! @$file = fopen($blAliveFileTmp, 'w') )
			throw new MxToolboxException('Cannot create new file: ' . $blAliveFileTmp);
		$this->loadBlacklistsFromFile('blacklists.txt');
		$this->buildTestArray();
		foreach ( $this->testResult as &$blackList ) {
			if ( $blackList['blResponse'] )
				fwrite( $file, $blackList['blHostName'].PHP_EOL );
		}
		unset($blackList);
		fclose($file);
		// check filesize
		if ( ! filesize($blAliveFileTmp) > 0 )
			throw new MxToolboxException('File is empty: ' . $blAliveFileTmp);
		// create new blacklist
		rename($blAliveFileTmp, $blAliveFileOrg);
		// load actual values
		$this->loadBlacklistsFromFile('blacklistsAlive.txt');
		$this->buildTestArray();
	}

	private function getUrlForPositveCheck($addr,$blackList) {
		$rIP = $this->reverseIP($addr);
		$checkResult = shell_exec( $this->digPath . ' @' . $this->getRandomDNSResolverIP() . ' +time=3 +tries=1 +noall +answer '.$rIP . '.' . $blackList.' TXT');
		$txtResult = explode(PHP_EOL, trim($checkResult));
		$matches = array();
		$URLs = array();
		foreach ($txtResult as $line) {
			if ( preg_match("/((\w+:\/\/)[-a-zA-Z0-9:@;?&=\/%\+\.\*!'\(\),\$_\{\}\^~\[\]`#|]+)/", $line, $matches) )
				$URLs[] = $matches[1];
		}
		return $URLs;
	}

	private function checkOnerBLSARecord($addr,$blackList) {
		$rIP = $this->reverseIP($addr);
		// TODO: random resolver from list of resolvers ?
		$checkResult = shell_exec($this->digPath . ' @' . $this->getRandomDNSResolverIP() . ' +time=5 +tries=1 +noall +answer '.$rIP . '.' . $blackList.' A');
		if ( !empty($checkResult) )
				return true;
		return false;
	}

	/**
	 * Build the array for check od DNSBL
	 */
	private function buildTestArray() {
		$this->testResult = array();
		$i = 0;
		foreach ($this->blackLists as $blackList) {
			$this->testResult[$i]['blHostName'] = $blackList;
			$this->testResult[$i]['blPositive'] = NULL;
			$this->testResult[$i]['blPositiveResult'] = NULL;
			$this->testResult[$i]['blResponse'] = false;

			// https://tools.ietf.org/html/rfc5782 cap. 5
			if ( $this->checkOnerBLSARecord('127.0.0.2', $blackList) )
				$this->testResult[$i]['blResponse'] = true;

			$i++;
		}
	}

	/**
	 * Reverse IP address 192.168.1.254 -> 254.1.168.192
	 * @param string $addr
	 * @return string
	 */
	private function reverseIP($addr) {
		$revIpAddr = explode( ".", $addr );
		return $revIpAddr[3] . '.' . $revIpAddr[2] . '.' . $revIpAddr[1] . '.' . $revIpAddr[0];
	}

	/**
	 * Load blacklists from the file $fileName to array
	 * @param string $fileName
	 * @throws MxToolboxException;
	 * @return mixed throw|boolean
	 */
	private function loadBlacklistsFromFile($fileName) {
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

	private function checkHostName($hostName) {
		$validHostnameRegex = "/^[a-zA-Z0-9.\-]{2,256}\.[a-z]{2,6}$/";
		if ( preg_match( $validHostnameRegex, trim($hostName) ) )
			return true;
		return false;
	}
	
	private function getRandomDNSResolverIP() {
		if ( ! count($this->resolvers) > 0 )
			throw new MxToolboxException('No DNS resolver here!');
		return $this->resolvers[array_rand($this->resolvers, 1)];
	}

}
