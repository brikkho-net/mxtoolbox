<?php

namespace MxToolbox\FileSystem;

use MxToolbox\Exceptions\MxToolboxRuntimeException;
use MxToolbox\Exceptions\MxToolboxLogicException;

class BlacklistsHostnameFile {

	/** @var array blacklists */
	private $blacklistHostNames;

	/**
	 * Get blacklist hostnames
	 * 
	 * @return array
	 * @throws MxToolboxLogicException
	 */
	public function &getBlacklistsHostNames() {
		if ( is_array($this->blacklistHostNames) && count($this->blacklistHostNames) > 0 )
			return $this->blacklistHostNames;
		throw new MxToolboxLogicException('Array is empty, load blacklist first.');
	}

	/**
	 * Load blacklists from the file
	 * 
	 * @param string $fileName
	 * @throws MxToolboxRuntimeException;
	 * @throws MxToolboxLogicException;
	 * @return $this
	 */
	public function loadBlacklistsFromFile($fileName) {
		$this->blacklistHostNames = array();
		$blFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . $fileName;
		if ( !is_readable( $blFile ) )
			throw new MxToolboxRuntimeException("Blacklists file is not readable: " . $blFile, 400);

		if ( !( $this->blacklistHostNames = file( $blFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES )) === false ) {
			if ( ! count($this->blacklistHostNames) > 0 ) {
				throw new MxToolboxLogicException("Blacklist file is empty: " . $blFile);
			}
			return $this;
		}
		throw new MxToolboxRuntimeException("Cannot get contents from blacklists file: " . $blFile, 500);
	}

	/**
	 * Build new file with alive DNSBLs hostnames (refresh is ideal for frequent testing)
	 * 
	 * @param array $aliveBlacklists
	 * @return $this
	 * @throws MxToolboxRuntimeException
	 */
	public function makeAliveBlacklistFile(&$aliveBlacklists) {
		//print_r($aliveBlacklists);
		//exit;
		if ( !array_key_exists('blHostName', $aliveBlacklists[0]))
			throw new MxToolboxLogicException("Cannot found index ['blHostName'] in array. Build test array first.");

			$blAliveFileTmp = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . $blAlivePath . 'blacklistsAlive.tmp';
		$blAliveFileOrg = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . $blAlivePath . 'blacklistsAlive.txt';

		// create temp file
		if (! @$file = fopen ( $blAliveFileTmp, 'w' ))
			throw new MxToolboxRuntimeException ( 'Cannot create new file: ' . $blAliveFileTmp );

		foreach ( $aliveBlacklists as &$blackList ) {
			if ( $blackList['blResponse'] )
				fwrite( $file, $blackList ['blHostName'] . PHP_EOL );
		}

		unset($blackList);
		fclose($file);

		// check filesize
		if ( !filesize( $blAliveFileTmp ) > 0) {
			@unlink($blAliveFileTmp);
			throw new MxToolboxRuntimeException ( 'Blacklist temp file is empty: ' . $blAliveFileTmp );
		}
		// create new blacklist
		if ( !rename( $blAliveFileTmp, $blAliveFileOrg ) )
			throw new MxToolboxRuntimeException( 'Cannot create Alive Blaclist file. Rename the file failed.' );

		return $this;
	}
	
}
