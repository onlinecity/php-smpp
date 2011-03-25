<?php
function __autoload($name)
{
	$parts = explode('\\',$name);
	
	// Figure out where to load files from
	$baseDir = strtolower($parts[0]);
	
	// Construct path to where we expect file to be, using each subnamespace as a directory
	$subnamespaces = array_slice($parts,1,-1);
	$subnamespaceString = !empty($subnamespaces) ? (strtolower(implode(DIRECTORY_SEPARATOR,$subnamespaces)).DIRECTORY_SEPARATOR) : '';
	$className = end($parts);
	$pathName = $baseDir . DIRECTORY_SEPARATOR . $subnamespaceString;

	// Try three common extensions .class.php, .interface.php and .php
	if (file_exists($pathName.strtolower($className).'.class.php')) {
		require_once($pathName.strtolower($className).'.class.php');
	} else if (file_exists($pathName.strtolower($className).'.interface.php')) { // also try .interface.php
		require_once($pathName.strtolower($className).'.class.php');
	} else if (file_exists($pathName.strtolower($className).'.php')) { // also try .php
		require_once($pathName.strtolower($className).'.php');
	} else {
		return;
	}
	
	return; // finally give up
}