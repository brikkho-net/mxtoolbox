<?php
/**
 * Parser for dig tool output
 * 
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 */
namespace MxToolbox\NetworkTools;

/**
 * Class DigQueryParser
 * @package MxToolbox\NetworkTools
 */
class DigQueryParser
{
    /*
    NOERROR (RCODE:0) : DNS Query completed successfully
    FORMERR (RCODE:1) : DNS Query Format Error
    SERVFAIL (RCODE:2) : Server failed to complete the DNS request
    NXDOMAIN (RCODE:3) : Domain name does not exist
    NOTIMP (RCODE:4) : Function not implemented
    REFUSED (RCODE:5) : The server refused to answer for the query
    YXDOMAIN (RCODE:6) : Name that should not exist, does exist
    XRRSET (RCODE:7) : RRset that should not exist, does exist
    NOTAUTH (RCODE:9) : Server not authoritative for the zone
    NOTZONE (RCODE:10) : Name not in zone
    */

    /**
     * Is NOERROR in dig answer
     * @param string $digOutput
     * @return bool
     */
    public function isNoError($digOutput)
    {
        if (preg_match('/NOERROR/', $digOutput))
            return true;
        return false;
    }

    /**
     * Is domain name in input string
     * @param string $domainName
     * @param string $digOutput
     * @return bool
     */
    public function isDomainNameInString($domainName,$digOutput)
    {
        if (preg_match('/'.$domainName.'/', $digOutput))
            return true;
        return false;
    }

    /**
     * Get positive url addresses from dig output
     * @param string $digOutput
     * @return array
     */
    public function getPositiveUrlAddresses($digOutput)
    {
        $txtResult = explode(PHP_EOL, trim($digOutput));
        $matches = array();
        $urlAddress = array();
        foreach ($txtResult as $line) {
            if (preg_match("/((\w+:\/\/)[-a-zA-Z0-9:@;?&=\/%\+\.\*!'\(\),\$_\{\}\^~\[\]`#|]+)/", $line, $matches))
                $urlAddress[] = $matches[1];
        }
        return $urlAddress;

    }

    /**
     * Get query time value fron dig output
     * @param $digOutput
     * @return string|bool
     */
    public function getQueryTime($digOutput)
    {
        if (preg_match("/\;; Query time:.*\b/", $digOutput, $matches))
            return filter_var($matches[0], FILTER_SANITIZE_NUMBER_INT);
        return false;
    }
    
}
