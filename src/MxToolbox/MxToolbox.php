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
		if ( !$this->loadBlacklistsFromFile() )
			throw new MxToolboxException('Load blacklists failed!');
	}
	
	/**
	 * Blacklists names
	 * @return array
	 */
	public function getBlackLists() {
		return $this->blackLists;
	}
	
	public function getTestResult() {
		return $this->testResult;
	}
	
	public function checkIPAddress($addr) {
		return filter_var ( $addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}
	
	
	
	public function testAllBlacklists($addr) {
		$this->buildTestArray();
		//print_r($this->testResult);
		foreach ($this->testResult as $blackList) {
//			if ( $blackList['blResponse'] ) {
				$this->easyTestOneBlacklist($addr, $blackList);
//			}
		}
	}
	
	private function easyTestOneBlacklist($addr,&$blackList) {
		if ( $reverseIP = $this->reverseIP($addr) ) {
//			$checkResult = dns_get_record($reverseIP . '.' . $blackList['blHostName'], DNS_TXT);
			echo $reverseIP . '.' . $blackList['blHostName'].' - ';
			$checkResult = shell_exec('dig @194.8.253.11 +time=3 +tries=1 +noall +answer '.$reverseIP . '.' . $blackList['blHostName'].' TXT');
			echo $checkResult.PHP_EOL;
			$blackList['blCheck'] = 'yes';
			if ( !empty($checkResult) ) {
				$blackList['blCheck'] = 'yes';
//				print_r($blackList);
			}
		}
	}
	
	/**
	 * Build array with blacklist for test, check if blaclist hostname exist
	 */
	private function buildTestArray() {
		$i = 0;
		foreach ($this->blackLists as $blackList) {
				$this->testResult[$i]['blHostName'] = $blackList;
//				$this->testResult[$i]['blResponse'] = false;
				$this->testResult[$i++]['blCheck'] = NULL;
		}
	}
	
	/**
	 * Reverse IP address 192.168.1.254 -> 254.1.168.192
	 * @param string $addr
	 * @return mixed (string or false on error)
	 */
	private function reverseIP($addr) {
		if ( $this->checkIPAddress($addr) ) {
			$revIpAddr = explode( ".", $addr );
			return $revIpAddr[3] . '.' . $revIpAddr[2] . '.' . $revIpAddr[1] . '.' . $revIpAddr[0];
		}
		return false;
	}
	
	/**
	 * Load blacklists from the file blacklists.txt to array
	 * @return boolean
	 */
	private function loadBlacklistsFromFile() {
		$blFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'blacklists.txt';
		if ( !is_readable( $blFile ) )
			return false;
		if ( ! ( $tmpBlacklists = file_get_contents( $blFile ) ) === false ) {
			$tmpBlacklists = explode( PHP_EOL, $tmpBlacklists );
			$tmpBlacklists = array_filter( $tmpBlacklists, array( $this, "clearBlacklistsArray" ));
			foreach ( $tmpBlacklists as $blackList )
				$this->blackLists[] = trim($blackList);
			if ( count($this->blackLists) > 0 ) 
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