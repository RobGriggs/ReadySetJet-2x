<?php
/**
 * Holds Autoloader Class
 */

 /**
  * Class autoloader
  * Initialization registers the autoload class function
  */
 class Autoloader
{
	/*
	 * Initialize, sets spl_autoload_register to class method autoload
	 */
	public function __construct()
	{
		spl_autoload_register(array($this, 'autoload'));
	}
	
	/**
	 * Sets autoloading behavior of application
	 * 
	 * behavior based on base name of file that initialized script
	 * for example, if application is started in the ajax.php file, autoloader looks in the views
	 * alternatively, if the application is started from the maintenance.php file, it will look
	 * in the maintenance folder for class files 
	 */
    public function autoload($class)
    {	
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
}
