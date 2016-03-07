<?php

namespace MxToolbox;

use MxToolbox\Exception\MxToolboxException;

class MxToolbox {
	/**
	 * Load blacklists from file
	 */
	const BLS_FROM_FILE = 'FILE';
	/**
	 * Load blacklists from database
	 */
	const BLS_FROM_MYSQL = 'MYSQL';

	private $dnsResolver;
	private $blacklists;

	/**
	 * MxToolbox
	 * @param string $dnsResolver - IP Address of your local DNS resolver
	 * @param MxToolbox $blsFrom - Load blacklists from (::BLS_FROM_FILE, ::BLS_FROM_MYSQL)
	 * @throws MxToolboxException
	 */
	public function __construct($dnsResolver, $blsFrom = self::BLS_FROM_FILE) {
		$this->dnsResolver = $dnsResolver;
		if ( empty($this->dnsResolver) )
			throw new MxToolboxException('IP Address of DNS Resolver is empty!');
		if ( !$this->checkIPAddress( $this->dnsResolver ) )
			throw new MxToolboxException($this->dnsResolver . ' is not IPv4 address!');
		if ( $blsFrom == self::BLS_FROM_FILE && !$this->loadBlacklistsFromFile() )
			throw new MxToolboxException('Load blacklists failed!');
		if ( $blsFrom == self::BLS_FROM_MYSQL )
			throw new MxToolboxException('Now not supported!');
		print_r($this->blacklists);
	}
	
	public function getDNSResolver() {
		return $this->dnsResolver;
	}
	
	public function digNiTo($addr) {
		return dns_get_record($addr, DNS_TXT);
	}
	
	//public function 
	
	protected function checkIPAddress($addr) {
		return filter_var ( $addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}
	
	/**
	 * Load blacklists from file
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
				$this->blacklists[] = trim($blackList);
			if ( count($this->blacklists) > 0 ) 
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