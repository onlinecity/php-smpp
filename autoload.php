<?php

function php_smpp_autoloader($name)
{
	$parts = explode('\\',$name);
	
	// Figure out where to load files from
	$baseDir = strtolower($parts[0]);
	
	// Construct path to where we expect file to be, using each subnamespace as a directory
	$subnamespaces = array_slice($parts,1,-1);
	$subnamespaceString = !empty($subnamespaces) ? (strtolower(implode(DIRECTORY_SEPARATOR,$subnamespaces)).DIRECTORY_SEPARATOR) : '';
	$className = strtolower(end($parts));
	$pathName = realpath( __DIR__ ) . DIRECTORY_SEPARATOR . $baseDir . DIRECTORY_SEPARATOR . $subnamespaceString;

	if(file_exists($pathName.$className.'.class.php')) 
	{
		require_once($pathName.$className.'.class.php');
	}
	
	return; // finally give up
}

spl_autoload_register('php_smpp_autoloader');

