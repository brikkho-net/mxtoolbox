<?php
function includeIfExists($file) {
	if (file_exists($file))
		return include $file;
}

if ( ( !$loader = includeIfExists(__DIR__.'/../src/MxToolbox/autoload.php') ) ) 
	die('Class loader error.');

//$loader->add('MXTests', __DIR__);

return $loader;
