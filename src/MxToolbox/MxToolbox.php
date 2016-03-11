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
	 * Get blacklists hostnames
	 * @return array
	 */
	public function getBlackLists() {
		return $this->blackLists;
	}
	
	/**
	 * Get test results
	 * @return array
	 */
	public function getTestResult() {
		return $this->testResult;
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
	//TODO: update check as https://en.wikipedia.org/wiki/DNSBL#DNSBL_queries ???
	//TODO: build only alive RBLs to separate bl. file (ideal for frequent testing)
	//TODO: check and return bool if is the IP in any RBLs
	
	public function testAllBlacklists($addr) {
		$this->buildTestArray();
		foreach ($this->testResult as &$blackList) {
			if ( $blackList['blResponse'] ) {
				echo "Check: " . $blackList['blHostName'] . PHP_EOL;
				if ( $this->easyTestOneBlacklist($addr, $blackList['blHostName']) ) {
					$blackList['blCheck'] = 'yes';
				}
			}
			else echo "Ignore: " . $blackList['blHostName'] . PHP_EOL;
		}
	}
	
	private function easyTestOneBlacklist($addr,$blackList) {
		if ( $reverseIP = $this->reverseIP($addr) ) {
			$checkResult = shell_exec('dig @194.8.253.11 +time=3 +tries=1 +noall +answer '.$reverseIP . '.' . $blackList.' TXT');
			if ( !empty($checkResult) )
				return true;
		}
		return false;
	}
	
	/**
	 * Build array with blacklist for test, check if blaclist hostname exist
	 */
	private function buildTestArray() {
		$i = 0;
		foreach ($this->blackLists as $blackList) {
			$this->testResult[$i]['blHostName'] = $blackList;
			$this->testResult[$i]['blCheck'] = NULL;
			$this->testResult[$i]['blPositiveURL'] = NULL;
			// https://tools.ietf.org/html/rfc5782 cap. 5
			if ( $this->easyTestOneBlacklist('127.0.0.2', $blackList)) {
				$this->testResult[$i++]['blResponse'] = true;
				continue;
			}
			$this->testResult[$i++]['blResponse'] = false;
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