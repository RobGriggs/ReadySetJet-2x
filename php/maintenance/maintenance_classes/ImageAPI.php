<?php

/*
 * Uses Bing Image Search
 * Call Limit: 5000 per month
 * Call timeout: ?
 * 
 */

class imageAPI
{
	private $apiKey;
	private $dataHandler;
	private $streamContext;
	private $apiCallLimitTime = 3; //seconds for rest
	
	public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->streamContext = $this->create_API_stream_context($apiKey);
        $this->dataHandler = Registry::get('DataHandler');
    }
	
	public function update_activity_images()
    {
        
        $time = microtime(true);
        
		$activities = $this->get_activities($this->dataHandler);
        
        foreach ($activities as $key => $values) {
            $activityImageURLs = $this->get_image_links_for_activity($this->streamContext, $values);           
    	    $this->dataHandler->update_activity_images($values['activity_id'], $activityImageURLs);
        }
        
        $timeNow = (microtime(true));
        $timePassed = $timeNow - $time;
        echo "finished activity image update in: ".$timePassed.' seconds';
    }

    // Encode the credentials and create the stream context. 	
	private function create_API_stream_context($apiKey)
	{
 
        $auth = base64_encode("$apiKey:$apiKey"); 
        
        $data = array( 
                'http' => array( 
                    'request_fulluri' => true, 
                    'ignore_errors' => false,
                    'header' => 'Authorization: Basic '.$auth
                        )
                    );
    
        $context = stream_context_create($data); 
		
        return $context;
	}
	
	private function get_activities(DataHandler $db)
    {
        $activities = $db->get_activity_data();
        return $activities;
    }
	
	private function get_image_links_for_activity($streamContext, $activityData)
	{
        //build the query from items in the database: activity type, activity, state);
        
        $activityType = $activityData['activity_type'];
        $activity = $activityData['activity'];
        $state = $activityData['state'];
    
        $searchFor = '\''.$activityType.' '.$activity.' '.$state.'\'';
        
        // warp the previous results in a URI friendly format for bing  
        $searchString = urlencode($searchFor);
        
        // the full search URL to be used
        $requestURI = 'https://api.datamarket.azure.com/Bing/Search/Image?$format=json&Query='.$searchString;
            
        // Get the response from Bing. With the exception of a total connection loss, Bing will always reply
        $parsed_response = file_get_contents($requestURI, 0, $streamContext); 
       
        //Decode the returned data
        $parsed_response = json_decode($parsed_response);
              
        // Check to see if a key object from the return data has a value to see if we can proceed with populating the database
        if (isset($parsed_response->d->results[0]->MediaUrl) == true) {
            
            $images = array();
            
            // if we got the right info, get 15 relevant image and thumbnail urls data from return data & store them in bing table
            for ($j = 1 ; $j <= 15 ; $j++) {
                
                // check to make sure that each URL is available - in case there are fewer than 15 results
                // if result is found, populate respective cell in table
                if (isset($parsed_response->d->results[$j]->MediaUrl) == true) {
                    
                    $img = $parsed_response->d->results[$j]->MediaUrl;
                    $thumbnail = $parsed_response->d->results[$j]->Thumbnail->MediaUrl;
                    
                    $images['t'.$j] = $thumbnail;
                    $images['p'.$j] = $img;             
                }
            }
        } else {
            sleep(10);
            return false;
        }
        sleep($this->apiCallLimitTime);
        return $images;
    }

}
