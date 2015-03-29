<?php
/**
 * The entry point for retriving views
 * 
 * A reqest for a view is passed to this file via a jQuery ajax request
 * Upon receiving a request, the application loads an initilaization script
 * and then sends the view request off to the view constructor
 */

 
initilize();
 
/**
 * initiallizes program on AJAX request
 */

function initilize(){
	
	require_once('../php/core/initialize_application.php');
	
	if (!empty($_POST['query'])) {
	    try { 
	        $requestedView = $_POST['query'];        
	        $view = new viewConstructor($requestedView);
	    } catch (exception $e) {
	        $logger = Registry::get('Logger');
	        $logger->logException($e);
	    }
	} else {
	    echo "Page Request Error"; 
	}
}
