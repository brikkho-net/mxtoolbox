<?php

	spl_autoload_register(
		function ($class) {
			static $map = [
					'MxToolbox\MxToolbox' => 'MxToolbox.php',
					'MxToolbox\Exception\MxToolboxException' => 'Exception/MxToolboxException.php'
			];
				
			if (isset($map[$class]))
				require __DIR__ . DIRECTORY_SEPARATOR . $map[$class];
		}
	);
