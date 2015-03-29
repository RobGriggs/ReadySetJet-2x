<?php

function autoload($class){
	
	$dir = dirname(__FILE__);
    
	//explode the path of the main script
	$paths = explode(PATH_SEPARATOR, $dir);
	
    //add additinal paths based on where the script was loaded from.
    $currentWorkingFile = basename(getcwd());
    $base = dirname(dirname(__FILE__));

    switch ($currentWorkingFile) {
        //main point of entry for front end
        case 'ajax':
            $paths[] = $base.'/front_end';
            $paths[] = $base.'/front_end/views';
            break;
        //main point of entry for site maintenance scripts    
        case 'maintenance':
            $paths[] = $base.'/maintenance/maintenance_classes';
            break;
        default:
            echo 'cannot load application';
            die();
    }        
        
	$flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
	$file = strtolower(str_replace('\\', DIRECTORY_SEPARATOR, trim($class, '\\'))).'.php';
	
	foreach($paths as $path)
	{
		$combined = $path.DIRECTORY_SEPARATOR.$file;
		
		if (file_exists($combined))
		{
			include($combined);
			return;
		}
	}
	throw new Exception("{$class} not found");
}

spl_autoload_register('autoload');


class Autoloader
{
    public static function autoload($class)
    {
        autoload($class);
    }   

}
