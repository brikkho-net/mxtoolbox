<?php
/**
 * MxToolBox
 * @version 0.0.1
 */
namespace MxToolbox;

use MxToolbox\AbstractMxToolbox;
use MxToolbox\Exception\MxToolboxException;

class MxToolbox extends AbstractMxToolbox {
	
	/**
	 * MxToolbox
	 * @param string $digPath - whereis dig
	 * @throws MxToolboxException
	 */
	public function __construct($digPath = '') {
		if ($digPath!='') {
			$this->digPath = $digPath;
			$this->checkDigPath();
		}
	}
	
	/**
	 * Load blacklist and create test array
	 * @throws MxToolboxException - if any error
	 */
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
				$mxRecords = array();
				foreach ($ptr as $mx)
					$mxRecords[] = $mx['target'];
				return $mxRecords;
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
		$this->checkDigPath();
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
		$this->checkDigPath();
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
	
	/**
	 * Check if path to the 'dig' exist
	 * @throws MxToolboxException
	 */
	public function checkDigPath() {
		if ( ! file_exists($this->digPath) )
			throw new MxToolboxException('DIG path: ' . $this->digPath . ' File does not exist!');
	}

}
