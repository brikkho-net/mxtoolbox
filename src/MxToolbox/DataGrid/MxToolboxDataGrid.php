<?php
namespace MxToolbox\DataGrid;

use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\DigTools\NetworkTools;

class MxToolboxDataGrid extends NetworkTools {

	protected $testResult;

	protected function &getTestResultArray() {
		if ( is_array($this->testResult) && count($this->testResult) > 0 )
			return $this->testResult;
		throw new MxToolboxLogicException('Array is empty, set test results array first.');
	}
	
	/**
	 * Build the array for check DNSBLs
	 * @param array $blacklistHostnamesArray
	 * @return $this
	 * @throws MxToolboxLogicException
	 */
	protected function setTestResultArray(&$blacklistHostnamesArray) {
		if ( is_array($blacklistHostnamesArray) && count($blacklistHostnamesArray) > 0 ) {
			$this->testResult = array();
			$index = 0;
			foreach ($blacklistHostnamesArray as $index => &$blackList) {
				$this->testResult[$index]['blHostName'] = $blackList;
				$this->testResult[$index]['blPositive'] = false;
				$this->testResult[$index]['blPositiveResult'] = array();
				$this->testResult[$index]['blResponse'] = false;
				$this->testResult[$index++]['blQueryTime'] = false;
			}
			unset($blackList);
			return $this;
		}
		throw new MxToolboxLogicException( get_class($this).' Input parameter is empty or is not a array.');
	}
	
	/**
	 * @see @link https://tools.ietf.org/html/rfc5782 cap. 5
	 * @return \MxToolbox\DataGrid\MxToolboxDataGrid
	 */
	protected function setDnsblResponse() {
		foreach ($this->testResult as $key => $val) {
			if ( $this->checkDnsblPtrRecord('127.0.0.2', $val['blHostName'], 'A') )
				$this->testResult[$key]['blResponse'] = true;
		}
		return $this;
	}
	
	
}