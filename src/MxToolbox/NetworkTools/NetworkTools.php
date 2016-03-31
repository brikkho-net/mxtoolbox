<?php
/**
 * Network tools
 *
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 */
namespace MxToolbox\NetworkTools;

use MxToolbox\Exceptions\MxToolboxLogicException;

/**
 * Class NetworkTools
 * @package MxToolbox\NetworkTools
 */
class NetworkTools
{

    /** @var array DNS resolvers IP addresses */
    private $dnsResolvers;
    /** @var string Path where is dig */
    private $digPath;

    /** Additional information for IP address
     * @see getDomainDetailInfo()
     */
    /** @var string PTR record of ip address */
    private $ptrRecord;
    /** @var string of a domain name for ip address */
    private $domainName;
    /** @var array of any mx records for ip address */
    private $mxRecords;

    /** @var DigQueryParser */
    private $digParser;

    /**
     * NetworkTools constructor.
     */
    public function __construct()
    {
        $this->digParser = new DigQueryParser();
    }

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
        if ($this->validateIPAddress($addr) && $fss = @fsockopen('tcp://' . $addr, 53, $errNo, $errStr, 5)) {
            fclose($fss);
            $this->dnsResolvers[] = $addr;
            return $this;
        }
        throw new MxToolboxLogicException('DNS Resolver: tcp://' . $addr . ':53 can\'t open listening socket.');
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
     * @param array $testResults
     * @return $this
     */
    public function setDnsblResponse(&$testResults)
    {
        foreach ($testResults as $key => $val) {
            if ($this->isDnsblResponse($val['blHostName']))
                $testResults[$key]['blResponse'] = true;
        }
        return $this;
    }

    /**
     * Get Dns resolvers array
     * @return array
     */
    public function &getDnsResolvers()
    {
        return $this->dnsResolvers;
    }

    /**
     * Get DIG path
     * @return string
     */
    public function &getDigPath()
    {
        return $this->digPath;
    }

    /**
     * Check DNSBL PTR Record
     * TODO: ipv6 support
     * @param string $addr
     * @param string $dnsResolver
     * @param string $blackList
     * @param string $record 'A,TXT,PTR,AAAA?', default 'A'
     * @return string
     */
    public function getDigResult($addr, $dnsResolver, $blackList, $record = 'A')
    {
        $checkResult = shell_exec($this->digPath . ' @' . $dnsResolver .
            ' +time=3 +tries=1 +nocmd ' . $this->reverseIP($addr) . '.' . $blackList . ' ' . $record);
        return $checkResult;
    }

    /**
     * Get some additional information about ip address as PTR,Domain name, MX records.
     * 
     *  $info = array(
     *      ['domainName'] => '',
     *      ['ptrRecord'] => '',
     *      ['mxRecords'] => array()
     *  );
     * @param string $addr IP address
     * @return array
     */
    public function getDomainDetailInfo(&$addr)
    {
        $this->setDigPath($this->digPath);
        $info = array();
        if ($this->checkExistPTR($addr)) {
            $info['domainName'] = $this->domainName;
            $info['ptrRecord'] = $this->ptrRecord;
            $info['mxRecords'] = array();
            if ($this->getMxRecords($this->domainName))
                $info['mxRecords'] = $this->mxRecords;
        }
        return $info;
    }

    /**
     * Check one hostname for response on 127.0.0.2
     * @param string $host
     * @return bool
     */
    public function isDnsblResponse(&$host)
    {
        $digOutput = $this->getDigResult('127.0.0.2', $this->getRandomDNSResolverIP(), $host, 'A');
        if ($this->digParser->isNoError($digOutput))
            return true;
        return false;
    }

    /**
     * Check all (use only alive rBLS - fast check!)
     * @param string $addr
     * @param array $testResult
     * @return $this
     * @throws MxToolboxLogicException
     */
    public function checkAllDnsbl($addr, &$testResult)
    {
        if ($this->validateIPAddress($addr) && count($testResult) > 0) {
            foreach ($testResult as &$blackList) {
                $digOutput = $this->getDigResult($addr, $this->getRandomDNSResolverIP(), $blackList['blHostName'], 'TXT');
                if ($this->digParser->isNoError($digOutput)) {
                    $blackList['blPositive'] = true;
                    $blackList['blPositiveResult'] = $this->digParser->getPositiveUrlAddresses($digOutput);
                } else {
                    $blackList['blResponse'] = false;
                }
                $blackList['blQueryTime'] = $this->digParser->getQueryTime($digOutput);
            }
            unset($blackList);
            return $this;
        }
        throw new MxToolboxLogicException(sprintf('Array is empty for dig checks in: %s\%s.', get_class(), __FUNCTION__));
    }

    /**
     * Reverse IP address 192.168.1.254 -> 254.1.168.192
     * @param string $addr
     * @return string
     */
    protected function reverseIP($addr)
    {
        $revIpAddr = explode(".", $addr);
        return $revIpAddr[3] . '.' . $revIpAddr[2] . '.' . $revIpAddr[1] . '.' . $revIpAddr[0];
    }

    /**
     * Check if string is valid IPv4 address or domain
     * @param string $addr
     * @return boolean
     */
    public function ipValidator($addr)
    {
        if ($this->isDomainName($addr)) {
            if ($this->validateIPAddress(gethostbyname($addr)))
                return true;
            return false;
        }

        if ($this->validateIPAddress($addr))
            return true;
        return false;
    }

    /**
     * Check if a string represents domain name
     * @param string $addr
     * @return boolean
     */
    public function isDomainName($addr)
    {
        if (preg_match("/^[a-zA-Z0-9.\-]{2,256}\.[a-z]{2,6}$/", $addr))
            return true;
        return false;
    }

    /**
     * Validate if string is valid IP address
     * @param string $addr
     * @return boolean
     */
    public function validateIPAddress($addr)
    {
        if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            return true;
        throw new MxToolboxLogicException('IP address: ' . $addr . ' is not valid.');
    }

    /**
     * Get random DNS IP address from array
     * @return string '<ip address>'
     * @throws MxToolboxLogicException
     */
    private function getRandomDNSResolverIP()
    {
        if (!count($this->dnsResolvers) > 0)
            throw new MxToolboxLogicException('No DNS resolver here!');
        return $this->dnsResolvers[array_rand($this->dnsResolvers, 1)];
    }

    /**
     * Get MX records from domain name
     * @param string $hostName
     * @return boolean
     */
    private function getMxRecords($hostName)
    {
        if (preg_match("/^[a-zA-Z0-9.\-]{2,256}\.[a-z]{2,6}$/", trim($hostName))) {
            $ptr = dns_get_record($hostName, DNS_MX);
            if (isset($ptr[0]['target'])) {
                $mxRecords = array();
                foreach ($ptr as $mx)
                    $mxRecords[] = $mx['target'];
                $this->mxRecords = $mxRecords;
                return true;
            }
        }
        return false;
    }

    /**
     * Check if IP address have a PTR record
     * @param string $addr
     * @return boolean
     */
    private function checkExistPTR($addr)
    {
        if (!$this->validateIPAddress($addr))
            return false;
        $digResponse = $this->getDigResult($addr, $this->getRandomDNSResolverIP(), 'in-addr.arpa', 'PTR');
        if ($this->digParser->isNoError($digResponse)) {
            $ptr = dns_get_record($this->reverseIP($addr) . '.in-addr.arpa.', DNS_PTR);
            if (isset($ptr[0]['target'])) {
                $regs = array();
                $this->ptrRecord = $ptr[0]['target'];
                if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $ptr[0]['target'], $regs))
                    $this->domainName = $regs['domain'];
                return true;
            }
        }
        return false;
    }

}
