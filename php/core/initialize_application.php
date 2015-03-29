<?php
/**
 * Holds script that initializes applicaion dependencies on startup
 * @author Rob Griggs
 * 
 * Initialization script that builds dependencies and populates registry with
 * globally needed classes 
 */

/*
 * Regardless of what directory we initialize from
 * we need to be able to load the config and our core dependencies, so we switch
 * working directories momentarily
 */
$callingDir = getcwd();

chdir(dirname(__FILE__));

require_once('../../config.php');
require_once('db_connect_function.php');

//setup Database connection
$db = connect_to_database($db_server_data, $transactionID);

//fire up logger / error handler
require_once('Logger.php');
$logger = Logger::get_instance($db, $transactionID);

//create Registry
require_once('Registry.php');
$registry = Registry::get_instance();

//register logger
$registry->set('Logger', $logger);

//create new data handler
require_once('DataHandler.php');
$dataHandler = DataHandler::get_instance($db);

//register data handler
$registry->set('DataHandler', $dataHandler);

/*
 * The autoloader makes decisions based on the directory that
 * calls the initialization script, so we have to set the working
 * directory back to what it originally was when a 'initialize_application.php' was called
 */

 //fire up the autoloader
chdir($callingDir);
require_once('autoloader.php'); 
