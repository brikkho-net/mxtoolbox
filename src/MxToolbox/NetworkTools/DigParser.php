<?php
/**
 * Created by PhpStorm.
 * User: hexim
 * Date: 24.3.16
 * Time: 14:48
 */

namespace MxToolbox\NetworkTools;

/**
 * Class DigParser
 * @package MxToolbox\NetworkTools
 */
class DigParser
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
    protected function isNoError(&$digOutput) {
        if (preg_match('/\NOERROR\b/',$digOutput))
            return true;
        return false;
    }

    /**
     * Get positive url addresses from dig output
     * @param string $digOutput
     * @return array
     */
    protected function getPositiveUrlAddresses(&$digOutput) {
        $txtResult = explode(PHP_EOL, trim($digOutput));
        $matches = array();
        $urlAddress = array();
        foreach ($txtResult as $line) {
            if (preg_match("/((\w+:\/\/)[-a-zA-Z0-9:@;?&=\/%\+\.\*!'\(\),\$_\{\}\^~\[\]`#|]+)/", $line, $matches))
                $urlAddress[] = $matches[1];
        }
        return $urlAddress;

    }

}