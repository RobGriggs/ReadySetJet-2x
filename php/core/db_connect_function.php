<?php
/**
 * Holds function used by initialization script to connect to database
 * @author Rob Griggs
 */

/**
 * Function used to make initial connection to database
 * 
 * @param array $server_data MySQL server information and login credentials
 * @param string $transactionID A unique identifier created each time the application is used
 * 
 * @return PDO <b>will log error in php.log and call die() on failure to connect<b>
 */

function connect_to_database($server_data, $transactionID) {
    try {
	
	    // variables reassigned because PDO is pickey about how parameters are passed.
	    $db_hostname = $server_data['db_hostname'];
	    $db_name = $server_data['db_name'];
	    $db_user = $server_data['db_user'];
	    $db_password = $server_data['db_password'];
	    $db_server = new PDO("mysql:host=$db_hostname;dbname=$db_name", "$db_user", "$db_password");
    
	} catch (PDOException $e) {
        
        $message = "Error Connecting to Database.";
        $errMsg = $message.' transaction ID: '.$transactionID;
        error_log($errMsg);
        echo $message;
        die();
    }
    return $db_server;
}