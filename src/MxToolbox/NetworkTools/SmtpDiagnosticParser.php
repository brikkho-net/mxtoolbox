<?php
/**
 * SMTP diagnostics parser
 *
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 */

namespace MxToolbox\NetworkTools;

/**
 * Class SmtpDiagnosticParser
 * @package MxToolbox\NetworkTools
 */
class SmtpDiagnosticParser
{
    /*
    200	(nonstandard success response, see rfc876)
    211	System status, or system help reply
    214	Help message
    220	<domain> Service ready
    221	<domain> Service closing transmission channel
    250	Requested mail action okay, completed
    251	User not local; will forward to <forward-path>
    252	Cannot VRFY user, but will accept message and attempt delivery
    354	Start mail input; end with <CRLF>.<CRLF>
    421	<domain> Service not available, closing transmission channel
    450	Requested mail action not taken: mailbox unavailable
    451	Requested action aborted: local error in processing
    452	Requested action not taken: insufficient system storage
    500	Syntax error, command unrecognised
    501	Syntax error in parameters or arguments
    502	Command not implemented
    503	Bad sequence of commands
    504	Command parameter not implemented
    521	<domain> does not accept mail (see rfc1846)
    530	Access denied (???a Sendmailism)
    550	Requested action not taken: mailbox unavailable
    551	User not local; please try <forward-path>
    552	Requested mail action aborted: exceeded storage allocation
    553	Requested action not taken: mailbox name not allowed
    554	Transaction failed
    */

    /**
     * Test if START TLS is present in EHLO array
     * @param array $smtpOutput Output array from EHLO SMTP command
     * @return bool
     */
    public function isTls(&$smtpOutput)
    {
        foreach ($smtpOutput as $value) {
            if (preg_match('/250\-STARTTLS/', $value))
                return true;
        }
        return false;
    }

    /**
     * Check if PTR record corresponds with SMTP EHLO answer
     * @param array $smtpOutput
     * @param string $ptrRecord
     * @return bool
     */
    public function isValidHostname(&$smtpOutput, &$ptrRecord)
    {
        foreach ($smtpOutput as $value) {
            if (preg_match('/250\-' . preg_quote(strtolower($ptrRecord), '.-') . '/', $value))
                return true;
        }
        return false;

    }

    /**
     * Find ip address from ptr record, TRUE = all OK and not a reverse DNS mismatch
     * @param string $addr IP address
     * @param array $aRecords array from dns_get_record($info['ptrRecord'], DNS_A)
     * @return bool
     */
    public function isReverseDnsMismatch(&$addr, $aRecords)
    {
        foreach ($aRecords as $idx => $value) {
            if ($value['ip'] == $addr)
                return true;
        }
        return false;
    }
}