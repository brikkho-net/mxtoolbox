<?php
namespace MxToolbox\NetworkTools;

use MxToolbox\Exceptions\MxToolboxLogicException;

//use MxToolbox\Exceptions\MxToolboxRuntimeException;

/**
 * Class NetworkTools
 * @package MxToolbox\NetworkTools
 */
class NetworkTools extends DigParser
{

    /** @var array DNS resolvers IP addresses */
    private $dnsResolvers;
    /** @var string Path where is dig */
    private $digPath;
    /** @var  NetworkTools obj */
    private $netTool;
    /** @var string PTR record from the method checkExistPTR() */
    private $recordPTR;
    /** @var string domain name from the method checkExistPTR() */
    private $domainName;
    
    /**
     * Push one IP address of a DNS resolver to the resolvers list
     * (tcp port 53 must be open)
     * (UDP sockets will sometimes appear to have opened without an error, even if the remote host is unreachable.)
     * (DNS Works On Both TCP and UDP ports)
     * @param string $addr
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function setDnsResolverIP($addr)
    {
        if ($this->validateIPAddress($addr) && $fss = @fsockopen('tcp://' . $addr, 53, $errno, $errstr, 5)) {
            fclose($fss);
            $this->dnsResolvers[] = $addr;
            return $this;
        }
        throw new MxToolboxLogicException('DNS Resolver: ' . $addr . ' do not response on port 53.');
    }
    
    /**
     * Set path to dig utility, etc: '/usr/bin/dig'
     * @param string $digPath
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function setDigPath($digPath)
    {
        if (!empty($digPath) && is_file($digPath)) {
            $this->digPath = $digPath;
            return $this;
        }
        throw new MxToolboxLogicException('DIG file does not exist!');
    }

    /**
     * Set 'blResponse' in testResult array on true if is dnsbl hostname alive
     * @see @link https://tools.ietf.org/html/rfc5782 cap. 5
     * @return \MxToolbox\DataGrid\MxToolboxDataGrid
     */
    public function setDnsblResponse(&$testResults)
    {
        foreach ($testResults as $key => $val) {
            if ($this->checkDnsblPtrRecord('127.0.0.2', $this->getRandomDNSResolverIP(), $val['blHostName'], 'A'))
                $testResults[$key]['blResponse'] = true;
        }
        return $this;
    }

    /**
     * Get Dns resolvers array
     * @return array
     */
    public function &getDnsResolvers() {
        return $this->dnsResolvers;
    }

    /**
     * Get DIG path
     * @return string
     */
    public function &getDigPath() {
        return $this->digPath;
    }
    
    /**
     * Get random DNS IP address from array
     * @return mixed
     * @throws MxToolboxLogicException
     */
    private function getRandomDNSResolverIP()
    {
        if (!count($this->dnsResolvers) > 0)
            throw new MxToolboxLogicException('No DNS resolver here!');
        return $this->dnsResolvers[array_rand($this->dnsResolvers, 1)];
    }

    /**
     * Check all (use only alive rBLS - fast check!)
     * @param string $addr
     * @return boolean - TRUE if process is done, FALSE on non valid IP address or if the blacklist is not loaded
     */
    public function checkAllDnsbl($addr,&$testResult)
    {
        if ($this->validateIPAddress($addr) && count($testResult) > 0) {
            foreach ($testResult as &$blackList) {
                //echo $addr.':'.$blackList['blHostName'].PHP_EOL;
                //continue;
                if ($this->checkDnsblPtrRecord($addr, $this->getRandomDNSResolverIP(), $blackList['blHostName'], 'A')) {
                    $blackList['blPositive'] = true;
                    //$blackList['blPositiveResult'] = $this->getUrlForPositveCheck($addr, $blackList['blHostName']);
                }
            }
            unset($blackList);
            return true;
        }
        $testResult = array();
        return false;
    }

    /**
     * Check DNSBL PTR Record
     * TODO: ipv6 support
     * TODO: +stats , parse query time
     * TODO: return string|boolean
     * @param string $addr
     * @param string $blackList
     * @param string $record 'A,TXT,AAAA?', default 'A'
     * @return boolean
     */
    public function checkDnsblPtrRecord($addr, $dnsResolver, $blackList, $record = 'A')
    {
        $reverseIp = $this->reverseIP($addr);
        // dig @194.8.253.11 -4 +noall +answer +stats 2.0.0.127.xbl.spamhaus.org A
        $checkResult = shell_exec($this->digPath . ' @' . $dnsResolver .
            ' +time=3 +tries=1 +noall +answer ' . $reverseIp . '.' . $blackList . ' ' . $record);
        if (!empty($checkResult))
            return true;
        return false;
    }




    /**
     * xxx
     * @param string $addr
     * @param string $blackList
     * @return array
     */
/*    protected function getUrlForPositveCheck($addr, $blackList)
    {
        $rIP = $this->reverseIP($addr);
        $checkResult = shell_exec($this->digPath . ' @' . $this->getRandomDNSResolverIP() .
            ' +time=3 +tries=1 +noall +answer ' . $rIP . '.' . $blackList . ' TXT');
        $txtResult = explode(PHP_EOL, trim($checkResult));
        $matches = array();
        $urlAddress = array();
        foreach ($txtResult as $line) {
            if (preg_match("/((\w+:\/\/)[-a-zA-Z0-9:@;?&=\/%\+\.\*!'\(\),\$_\{\}\^~\[\]`#|]+)/", $line, $matches))
                $urlAddress[] = $matches[1];
        }
        return $urlAddress;
    }*/

    /**
     * Check string is domain like
     * @param string $hostName
     * @return bool
     */
    public function checkHostName($hostName)
    {
        $validHostnameRegex = "/^[a-zA-Z0-9.\-]{2,256}\.[a-z]{2,6}$/";
        if (preg_match($validHostnameRegex, trim($hostName)))
            return true;
        return false;
    }

     /**
     * Reverse IP address 192.168.1.254 -> 254.1.168.192
     * @param string $addr
     * @return string
     */
    private function reverseIP($addr)
    {
        $revIpAddr = explode(".", $addr);
        return $revIpAddr[3] . '.' . $revIpAddr[2] . '.' . $revIpAddr[1] . '.' . $revIpAddr[0];
    }

    /**
     * Validate if string is valid IP address
     * @param string $addr
     * @return boolean
     */
    protected function validateIPAddress($addr)
    {
        if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            return true;
        throw new MxToolboxLogicException('IP address: ' . $addr . ' is not valid.');
    }

}
