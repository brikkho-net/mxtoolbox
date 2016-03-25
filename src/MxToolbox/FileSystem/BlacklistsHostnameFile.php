<?php
namespace MxToolbox\FileSystem;

use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;

/**
 * Class BlacklistsHostnameFile
 * @package MxToolbox\FileSystem
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://best-hosting.cz
 */
class BlacklistsHostnameFile
{

    /** @var array blacklists */
    private $blacklistHostNames;

    /** @var string path to blacklist files folder */
    private $blacklistPath;

    /**
     * BlacklistsHostnameFile constructor.
     */
    public function __construct()
    {
        $this->blacklistPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get blacklists host names
     *
     * @return array
     * @throws MxToolboxLogicException
     */
    public function &getBlacklistsHostNames()
    {
        if (is_array($this->blacklistHostNames) && count($this->blacklistHostNames) > 0)
            return $this->blacklistHostNames;
        throw new MxToolboxLogicException('Array is empty, load blacklist first.');
    }

    /**
     * Load blacklists host names from a file
     *
     * @param string $fileName
     * @throws MxToolboxRuntimeException;
     * @throws MxToolboxLogicException;
     * @return $this
     */
    public function loadBlacklistsFromFile($fileName)
    {
        $blFile = $this->blacklistPath . $fileName;
        if (!is_readable($blFile))
            throw new MxToolboxRuntimeException("Blacklists file does not exist", 400);

        if (!($this->blacklistHostNames = file($blFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES)) === false) {
            if (!count($this->blacklistHostNames) > 0) {
                throw new MxToolboxLogicException(sprintf('Blacklist file' . $blFile . ' is empty in %s\%s()',
                    get_class(), __FUNCTION__));
            }
            return $this;
        }
        throw new MxToolboxRuntimeException(sprintf('Cannot get contents from: ' . $blFile . ' in %s\%s()',
            get_class(), __FUNCTION__), 500);
    }

    /**
     * Build new file with alive DNSBLs host names
     *
     * @param array $aliveBlacklists
     * @return $this
     * @throws MxToolboxRuntimeException
     * @throws MxToolboxLogicException
     */
    public function makeAliveBlacklistFile(&$aliveBlacklists)
    {
        if (!array_key_exists('blHostName', $aliveBlacklists[0]))
            throw new MxToolboxLogicException("Cannot found index ['blHostName'] in array. Build test array first.");

        $blAliveFileTmp = $this->blacklistPath . 'blacklistsAlive.tmp';
        $blAliveFileOrg = $this->blacklistPath . 'blacklistsAlive.txt';

        // create temp file
        if (!@$file = fopen($blAliveFileTmp, 'w'))
            throw new MxToolboxRuntimeException ('Cannot create new file: ' . $blAliveFileTmp);

        foreach ($aliveBlacklists as &$blackList) {
            if ($blackList['blResponse'])
                fwrite($file, $blackList ['blHostName'] . PHP_EOL);
        }

        unset($blackList);
        fclose($file);

        // check file size
        if (!filesize($blAliveFileTmp) > 0) {
            @unlink($blAliveFileTmp);
            throw new MxToolboxRuntimeException ('Blacklist temp file is empty: ' . $blAliveFileTmp);
        }
        // create new blacklist file from temp
        if (!rename($blAliveFileTmp, $blAliveFileOrg))
            throw new MxToolboxRuntimeException('Cannot create Alive Blacklist file. Rename the file failed.');

        return $this;
    }

    /**
     * Delete alive blacklists file
     * @return $this
     * @throws MxToolboxRuntimeException
     */
    public function deleteAliveBlacklist()
    {
        $blAliveFile = $this->blacklistPath . 'blacklistsAlive.txt';
        if (!is_readable($blAliveFile))
            throw new MxToolboxRuntimeException("File does not exist: " . $blAliveFile);
        @unlink($blAliveFile);
        return $this;

    }

}
