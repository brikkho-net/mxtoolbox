<?php

namespace MxToolbox;

use MxToolbox\DataGrid\MxToolboxDataGrid;

abstract class AbstractMxToolbox extends MxToolboxDataGrid {
	
	/**
	 * @var string PTR record from the method checkExistPTR()
	 */
	protected $recordPTR;
	/**
	 * @var string domain name from the method checkExistPTR()
	 */
	protected $domainName;
	/**
	 * var string DNSBL array 
	 */
	//protected $blackLists;
	/**
	 * var string DNSBL test results
	 */
	//protected $testResult;
	/**
	 * var string whereis dig
	 */
	//protected $digPath;
	/**
	 * var array DNS resolvers IP addresses
	 */
	//protected $resolvers;
	
}
