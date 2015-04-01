<?php
/*
 * Holds configuration variables used by application for database access and API query authentication
 */
date_default_timezone_set("America/Chicago"); 

//git ignored file with credential information
require_once('credentials.php');

$db_server_data = array( 
                      'db_hostname' => $keys['dbHost'],
                      'db_name' => $keys['dbTable'],
                      'db_user' => $keys['dbUser'],
                      'db_password' => $keys['dbPass'],
                    );

$transactionID = microtime(true);

$weatherAPIKey = $keys['weatherAPI'];
$hotwireAPIKey = $keys['hotwireAPI'];
$microsoftAPIKey = $keys['bingAPI'];
