<?php

	spl_autoload_register(
		function ($class) {
			static $map = [
					'MxToolbox\MxToolbox' => 'MxToolbox.php',
					'MxToolbox\AbstractMxToolbox' => 'AbstractMxToolbox.php',
					'MxToolbox\Exceptions\MxToolboxLogicException' => 'Exceptions/MxToolboxExceptions.php',
					'MxToolbox\Exceptions\MxToolboxRuntimeException' => 'Exceptions/MxToolboxExceptions.php',
					'MxToolbox\FileSystem\BlacklistsHostnameFile' => 'FileSystem/BlacklistsHostnameFile.php',
					'MxToolbox\DataGrid\MxToolboxDataGrid' => 'DataGrid/MxToolboxDataGrid.php',
					'MxToolbox\DigTools\DigDnsTool' => 'DigTools/DigDnsTool.php'
			];
				
			if (isset($map[$class]))
				require __DIR__ . DIRECTORY_SEPARATOR . $map[$class];
		}
	);
