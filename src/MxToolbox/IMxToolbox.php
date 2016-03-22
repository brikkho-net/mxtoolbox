<?php

namespace MxToolbox;

interface IMxToolbox {

	/**
	 * Load blacklist and create test array
	 * @throws MxToolboxException
	 */
	public function loadBlacklist();

	/**
	 * Get blacklists hostnames
	 * @return array
	 */
	public function getBlackLists();

	/**
	 * Get test results
	 * @return mixed array|bool
	 */
	public function getCheckResult();

	/**
	 * Get PTR record
	 * @return mixed string|bool
	 */
	public function getPTR();

	/**
	 * Get domain name
	 * @return mixed string|bool
	 */
	public function getDomainName();

	/**
	 * Get MX records from domain name
	 * @param string $hostName
	 * @return mixed - ARRAY with MX records or FALSE
	 */
	public function getMXRecords($hostName);

	/**
	 * Check if IP address have a PTR record
	 * @param string $addr
	 * @return boolean
	 */
	public function checkExistPTR($addr);

	/**
	 * Push IP address of a DNS resolver to the reslovers list
	 * (tcp port 53 must be open)
	 * (UDP sockets will sometimes appear to have opened without an error, even if the remote host is unreachable.)
	 * (DNS Works On Both TCP and UDP ports)
	 * @param string $addr
	 * @return boolean
	 */
	public function pushDNSResolverIP($addr);

	/**
	 * Validate if string is valid IP address
	 * @param string $addr
	 * @return boolean
	 */
	public function validateIPAddress($addr);

	/**
	 * Check all (use only alive rBLS - fast check!)
	 * @param string $addr
	 * @return boolean - TRUE if process is done, FALSE on non valid IP address or if the blacklist is not loaded 
	 */
	public function checkAllrBLS($addr);

	/**
	 * Check if path to the 'dig' exist
	 * @throws MxToolboxException
	 */
	public function checkDigPath();

}