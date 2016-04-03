<?php
/**
 * Autoload file
 */
spl_autoload_register(
    function ($class) {
        static $map = [
            'MxToolbox\MxToolbox' => 'MxToolbox.php',
            'MxToolbox\IMxToolbox' => 'IMxToolbox.php',
            'MxToolbox\Container\MxToolboxContainer' => 'Container/MxToolboxContainer.php',
            'MxToolbox\Exceptions\MxToolboxLogicException' => 'Exceptions/MxToolboxLogicExceptions.php',
            'MxToolbox\Exceptions\MxToolboxRuntimeException' => 'Exceptions/MxToolboxRuntimeExceptions.php',
            'MxToolbox\FileSystem\BlacklistsHostnameFile' => 'FileSystem/BlacklistsHostnameFile.php',
            'MxToolbox\DataGrid\MxToolboxDataGrid' => 'DataGrid/MxToolboxDataGrid.php',
            'MxToolbox\NetworkTools\NetworkTools' => 'NetworkTools/NetworkTools.php',
            'MxToolbox\NetworkTools\SmtpServerChecks' => 'NetworkTools/SmtpServerChecks.php',
            'MxToolbox\NetworkTools\SmtpDiagnosticParser' => 'NetworkTools/SmtpDiagnosticParser.php',
            'MxToolbox\NetworkTools\DigQueryParser' => 'NetworkTools/DigQueryParser.php'
        ];

        if (isset($map[$class]))
            require __DIR__ . DIRECTORY_SEPARATOR . $map[$class];
    }
);
