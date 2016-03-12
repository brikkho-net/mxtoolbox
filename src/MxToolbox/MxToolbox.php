<?php

namespace MxToolbox;

use MxToolbox\Exception\MxToolboxException;

class MxToolbox {
	
	private $blackLists;
	private $testResult;

	/**
	 * MxToolbox
	 * @throws MxToolboxException
	 */
	public function __construct() {
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
	
	public function checkIPAddress($addr) {
		return filter_var ( $addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}
	
	//TODO: check reverse DNS for IP
	//TODO: check domain have right MX
	//TODO: add blacklist for MX in DNS

	//TODO: filter url for positive checks:
	/*
	hexim@hexim-nb:~/workspace/php/mxtoolbox$ dig +noall +short 2.0.0.127.zen.spamhaus.org TXT @194.8.253.11
	 "https://www.spamhaus.org/query/ip/127.0.0.2"
	 "https://www.spamhaus.org/sbl/query/SBL233"
	hexim@hexim-nb:~/workspace/php/mxtoolbox$ dig +noall +short 2.0.0.127.spam.rbl.msrbl.net TXT @194.8.253.11
	 "SPAM Sending Host - see http://www.msrbl.com/check?ip=127.0.0.2"
	*/
	//TODO: check and return bool if is the IP in any RBLs
	
	/**
	 * Check all (use only alive rBLS - fast check!)
	 * @param string $addr
	 * @return boolean (if ip address is not valid)
	 */
	public function checkAllrBLS($addr) {
		if ( $this->checkIPAddress($addr) ) {
			foreach ($this->testResult as &$blackList) {
				if ( $this->checkOnerBLSARecord($addr, $blackList['blHostName']) ) {
					$blackList['blCheck'] = 'yes'; //TODO: change to true after testing! 
					$blackList['blPositiveResult'] = $this->getUrlForPositveCheck($addr, $blackList['blHostName']);
				}
			}
			unset($blackList);
			return true;
		}
		$this->testResult = false;
		return false;
	}
	
	/**
	 * Build new file with alive DNSBLs hostnames
	 * (ideal for frequent testing)
	 * @return boolean
	 */
	public function makeAliveBlacklistFile() {
		$blAlivePath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
		$blAliveFileTmp = $blAlivePath . 'blacklistsAlive.tmp';
		$blAliveFileOrg = $blAlivePath . 'blacklistsAlive.txt';
		// create temp file
		if (! @$file = fopen($blAliveFileTmp, 'w') )
			throw new MxToolboxException('Cannot create new file: ' . $blAliveFileTmp);
		$this->loadBlacklistsFromFile('blacklists.txt');
		$this->buildTestArray();
		foreach ( $this->testResult as $blackList ) {
			if ( $blackList['blResponse'] )
				fwrite( $file, $blackList['blHostName'].PHP_EOL );
		}
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
		$checkResult = shell_exec('dig @194.8.253.11 +time=3 +tries=1 +noall +answer '.$rIP . '.' . $blackList.' TXT');
		$txtResult = explode(PHP_EOL, trim($checkResult));
		$matches = array();
		$URLs = array();
		foreach ($txtResult as $line) {
			preg_match("/((\w+:\/\/)[-a-zA-Z0-9:@;?&=\/%\+\.\*!'\(\),\$_\{\}\^~\[\]`#|]+)/", $line, $matches);
			if ( isset($matches[1]) ) 
				$URLs[] = $matches[1];
		}
		return $URLs;
	}
	
	private function checkOnerBLSARecord($addr,$blackList) {
		$rIP = $this->reverseIP($addr);
		$checkResult = shell_exec('dig @194.8.253.11 +time=3 +tries=1 +noall +answer '.$rIP . '.' . $blackList.' A');
		if ( !empty($checkResult) )
				return true;
		return false;
	}
	
	/**
	 * Build array with blacklist for test, 
	 */
	private function buildTestArray() {
		$this->testResult = array();
		$i = 0;
		foreach ($this->blackLists as $blackList) {
			$this->testResult[$i]['blHostName'] = $blackList;
			$this->testResult[$i]['blCheck'] = NULL;
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
	 * @return mixed (string or false on error)
	 */
	private function reverseIP($addr) {
		$revIpAddr = explode( ".", $addr );
		return $revIpAddr[3] . '.' . $revIpAddr[2] . '.' . $revIpAddr[1] . '.' . $revIpAddr[0];
	}
	
	/**
	 * Load blacklists from the file $fileName to array
	 * @param string $fileName
	 * @throws MxToolboxException;
	 * @return mixed throw|bool
	 */
	private function loadBlacklistsFromFile($fileName) {
		$this->blackLists = array();
		$blFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . $fileName;
		if ( !is_readable( $blFile ) )
			return false;
		if ( ! ( $tmpBlacklists = file_get_contents( $blFile ) ) === false ) {
			$tmpBlacklists = explode( PHP_EOL, $tmpBlacklists );
			$tmpBlacklists = array_filter( $tmpBlacklists, array( $this, "clearBlacklistsArray" ));
			foreach ( $tmpBlacklists as $blackList )
				$this->blackLists[] = trim($blackList);
			if ( ! count($this->blackLists) > 0 ) 
				throw new MxToolboxException("Blacklist is empty: " . $blFile);
			return true;
		}
		return false;
	}
	
	private function clearBlacklistsArray($blacklist) {
		$validHostnameRegex = "/^[a-zA-Z0-9.\-]{2,256}\.[a-z]{2,6}$/";
		if ( preg_match( $validHostnameRegex, trim($blacklist) ) )
			return true;
		return false;
	}
	
/*
dig +short TXT 5.253.8.194.zen.spamhaus.org @194.8.253.11
	"https://www.spamhaus.org/query/ip/127.0.0.2"
	"https://www.spamhaus.org/sbl/query/SBL233"
dig +short 2.0.0.127.zen.spamhaus.org @194.8.253.11
	127.0.0.4
	127.0.0.2
	127.0.0.10
*/
	
	
}