<?php

//Car and Hotel Data is handled by 
Class CarAndHotelAPI
{
	
    /**
     * API CALL LIMITS IN PLACE:
     * CALL ALLOWANCE: 5,000 per day
     * SPEED ALLOWANCE: 2 requests per second 
     */
    
	private $apiKey; 
	private $database; 
    private $dataHandler;
    private $locations; //key value array, location => location_id ex. airport => airport_id
    private $queryDates; //key value array, 
	
	//call limit time, to be set as long, or as longer than the allowed time between API calls 
	private $callLimitTime = 3; //seconds
	private $daysToRetrieve = 10; //the number of days that rates will be checked for, careful this will multiply the number of request for each airport
	
	//These times are ideal because all car agencies are open at these hours, preventing errors from hotwire about any local agencies being closed
	private $pickupTime = "20:00" ;
	private $dropoffTime = "20:00";
	
	function __construct($apiKey)
	{
		$this->apiKey = $apiKey;
        
        $registry = Registry::get_instance();
        $this->dataHandler = $registry->get('DataHandler');
        	
        $this->locations = $this->dataHandler->get_airports();
        
        
        //for debugging
        //$this->locations = $this->airports = array( array( 'airport_id' =>'78', 'airport_code' => 'HNL'));
        
        $this->queryDates = $this->generate_query_dates();
    }
    
    public function update_rates()
    {
        $this->update_car_and_hotel_rates($this->database, $this->locations, $this->queryDates);
    }
    
    
    //array containing the next ten days in format mm/dd/yyyy
    private function generate_query_dates()
    {
        $dates = array();
        
        for( $i=0; $i<=9; $i++){
            $dates[] = date('m/d/Y', strtotime('+'.$i.'days')); 
        }
                
        return $dates; 
    }
    
    //engine function that drives rate updates
	private function update_car_and_hotel_rates($db, $airports, $dates)
	{
	    $updateDayNumber = 0;
        
	    //foreach date
	    foreach($dates as $startDate){
	    
            //foreach airport    
            foreach($airports as $airport => $airportData){
                
                //query car rate
                $carRate = $this->get_car_rate($this->apiKey, $airportData['airport_code'], $startDate, $this->pickupTime, $this->dropoffTime, $this->callLimitTime);
                        
                //save car rate to database
                $this->dataHandler->update_carhotel_rate($airportData['airport_id'], 'car', $updateDayNumber, $carRate);

                //query hotel rate
                $hotelRates = $this->get_hotel_rate($this->apiKey, $airportData['airport_code'], $startDate, $this->callLimitTime);
                
                //save hotel rate to database
                $this->dataHandler->update_carhotel_rate($airportData['airport_id'], 'hotel', $updateDayNumber, $hotelRates['min'], $hotelRates['max']);
                
            }
        
            $updateDayNumber++;
        
	    }
    }
    
    private function get_car_rate($apiKey, $airportCode, $startDate, $pickupTime, $dropoffTime, $callLimitTime)
    {
        $endDate = date('m/d/Y',strtotime($startDate . "+1 days"));
            
        // This is the querry to hotwire
        $xml_query_string = 'http://api.hotwire.com/v1/search/car?apikey='.$apiKey.'&dest='.$airportCode.'&startdate='.$startDate.'&enddate='.$endDate.'&pickuptime='.$pickupTime.'&dropofftime='.$dropoffTime;       
    
        // If the returned xml file has a key object value, populate database from the XML
        $parsed_xml = simplexml_load_file($xml_query_string);

        if (isset($parsed_xml->Result->CarResult[1]->TotalPrice) == true){
    
            $avgcar = $parsed_xml->Result->CarResult[1]->TotalPrice;
            $avgcar = round($avgcar, 2);
            $avgcar = number_format((float)$avgcar, 2, '.', '');
            
            //Just in case I ever wanna add Premium Car data for fun
            //Following to parse Premium Car Data
            //$premium = $parsed_xml->Result->CarResult[5]->TotalPrice / 3;
            //$premium = round($premium, 2);
            //$premium = number_format((float)$premium, 2, '.', '');
            
            sleep($callLimitTime);
            
            return $avgcar;   
        
        } else {
            
            sleep(10);   
            return "x";
            
        }
    }
    

    private function get_hotel_rate($apiKey, $airportCode, $startDate, $callLimitTime)
    {
                
        $rooms = 1;
        $adults = 1;
        $children = 0;
        
        //query requires an end date, we'll set it for tomorrow        
        $endDate = date('m/d/Y',strtotime($startDate . "+1 days"));

        // This is the querry to hotwire
        $xml_query_string = 'http://api.hotwire.com/v1/deal/hotel?apikey='.$apiKey.'&dest='.$airportCode.'&rooms='.$rooms.'&adults='.$adults.'&children='.$children.'&startdate='.$startDate.'&enddate='.$endDate;       
        
        $parsed_xml = simplexml_load_file($xml_query_string);   
        
        $rates = array();
        
        $lowStar = 3;
        $highStar = 3.5;
        
        if (isset($parsed_xml->Result->HotelDeal[0])){
    
            foreach($parsed_xml->Result->HotelDeal as $key => $values){
                
                $hotelStar = $values->StarRating[0];
                
                if($lowStar <= $hotelStar && $hotelStar <= $highStar){
                    
                    $rate = ($values->Price);
                    
                    //Las Vegas Hotels, on average, underreport an average of 20 dollars for each day of the bill. The unreported charge is in "resort fees".
                    if ($airportCode == 'LAS') {
                        $rate += 20;
                    }
                    
                    $rates[] = $rate;
                }
            }

            sleep($callLimitTime);

            if(empty($rates)){
                
                $returnRates['min'] = 'no 3 stars';
                $returnRates['max'] = 'no 3 stars';
                
                return $returnRates;
            
            }else{
                $min = intval(min($rates));
                $max = intval(max($rates));
                
                echo $airportCode.PHP_EOL;
                print_r($rates).PHP_EOL.PHP_EOL;
                
                echo 'min: '.$min.PHP_EOL;
                echo 'max: '.$max.PHP_EOL;
                
                $returnRates = array();
                $returnRates['min'] = $min;
                $returnRates['max'] = $max;
                return $returnRates;
            }
        
        } else {
            
            sleep(10);
            
            return 'x';
        }
    }
}
