<?php
namespace MxToolbox\DigTools;

use MxToolbox\Exceptions\MxToolboxLogicException;

class DigDnsTool {

	/** @var array DNS resolvers IP addresses */
	protected $resolvers;
	/** @var string Path where is dig */
	protected $digPath;
	
	protected function getUrlForPositveCheck($addr,$blackList) {
		$rIP = $this->reverseIP($addr);
		$checkResult = shell_exec( $this->digPath . ' @' . $this->getRandomDNSResolverIP() . 
				' +time=3 +tries=1 +noall +answer '.$rIP . '.' . $blackList.' TXT');
		$txtResult = explode(PHP_EOL, trim($checkResult));
		$matches = array();
		$urlAddress = array();
		foreach ($txtResult as $line) {
			if ( preg_match("/((\w+:\/\/)[-a-zA-Z0-9:@;?&=\/%\+\.\*!'\(\),\$_\{\}\^~\[\]`#|]+)/", $line, $matches) )
				$urlAddress[] = $matches[1];
		}
		return $urlAddress;
	}
	
	/**
	 * Check DNSBL PTR Record
	 * TODO: ipv6 support
	 * TODO: +stats , parse query time
	 * TODO: return string|boolean
	 * @param string $addr
	 * @param string $blackList
	 * @param string $record 'A,TXT,AAAA', default 'A'
	 * @return boolean
	 */
	protected function checkDnsblPtrRecord($addr,$blackList,$record = 'A') {
		$rIP = $this->reverseIP($addr);
		// dig @194.8.253.11 -4 +noall +answer +stats 2.0.0.127.xbl.spamhaus.org A
		$checkResult = shell_exec( $this->digPath . ' @' . $this->getRandomDNSResolverIP() . 
				' +time=3 +tries=1 +noall +answer '.$rIP . '.' . $blackList.' '.$record );
		if ( !empty($checkResult) )
			return true;
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
			throw new MxToolboxLogicException('No DNS resolver here!');
		return $this->resolvers[array_rand($this->resolvers, 1)];
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
	 * Push IP address of a DNS resolver to the reslovers list
	 * (tcp port 53 must be open)
	 * (UDP sockets will sometimes appear to have opened without an error, even if the remote host is unreachable.)
	 * (DNS Works On Both TCP and UDP ports)
	 * @param string $addr
	 * @return $this
	 * @throws MxToolboxLogicException
	 */
	public function pushDNSResolverIP($addr) {
		$errno = ''; $errstr = '';
		if ( $this->validateIPAddress($addr) && $fss = @fsockopen( 'tcp://'.$addr, 53, $errno, $errstr, 5 )) {
			fclose($fss);
			$this->resolvers[] = $addr;
			return $this;
		}
		throw new MxToolboxLogicException('DNS Resolver: '.$addr.' do not response on port 53.');
	}
	
	/**
	 * Set path to dig utility, etc: '/usr/bin/dig'
	 * @param string $digPath
	 * @return $this
	 * @throws MxToolboxLogicException
	 */
	public function setDigPath($digPath) {
		if ( $digPath!='' ) {
			$this->digPath = $digPath;
			$this->checkDigPath();
			return $this;
		}
		throw new MxToolboxLogicException('Dig path is empty.');
	}

	/**
	 * Validate if string is valid IP address
	 * @param string $addr
	 * @return boolean
	 */
	protected function validateIPAddress($addr) {
		if (filter_var ( $addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ))
			return true;
		throw new MxToolboxLogicException('IP address '.$addr.' is not valid.');
	}
	
	/**
	 * Check if path to the 'dig' exist
	 * @throws MxToolboxLogicException
	 */
	protected function checkDigPath() {
		if ( ! file_exists($this->digPath) )
			throw new MxToolboxLogicException('DIG path: ' . $this->digPath . ' File does not exist!');
		return $this;
	}
	
}