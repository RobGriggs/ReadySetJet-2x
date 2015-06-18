<?php
/**
 * Holds CarAndHotelAPI class
 */
 
/**
 * Queries Hotwire API for car and hotel rental rates, parses data and pushes back to application DB
 *
 * API CALL LIMITS IN PLACE:
 * CALL ALLOWANCE: 5,000 per day
 * SPEED ALLOWANCE: 2 requests per second 
 */
Class CarAndHotelAPI
{
	/**
	 * @var string $apiKey
	 * @var DataHandler $dataHandler
	 * @var array $locations key value array, values include location (mainly in form of airport code) and location_id
	 * @var array $queryDates dates to query, formatted mm/dd/yyyy
	 * @var array $daysToRetrieve Number of days in future that rates will be checked for, careful each additional day will multiply the number of API calls substantially
	 * @var string $pickupTime 	Ideal car pickup time because all car agencies open by this hour, preventing errors from hotwire about any local agencies being closed
	 * @var string $dropoffTime Ideal car dropoff time because all car agencies are still open at this hour, preventing errors from hotwire about any local agencies being closed
	 */
	private $apiKey, 
    		$dataHandler,
    		$locations,
    		$queryDates,
			$daysToRetrieve = 10, 
			$callLimitTime = 3,
			$pickupTime = "20:00",
			$dropoffTime = "20:00";
	
	/**
	 * Initialize class, retrieve location data from DB, generate calendar dates to query against
	 * @param string $apiKey authentication string used used query Hotwire.com
	 */
	function __construct($apiKey)
	{
		$this->apiKey = $apiKey;
        
        $registry = Registry::get_instance();
        $this->dataHandler = $registry->get('DataHandler');
        	
        $this->locations = $this->dataHandler->get_airports();
        $this->queryDates = $this->generate_query_dates();
    }
    
	/**
	 * Call to retrieve new car and hotel rates for application locations and to push said results to database
	 * @return void
	 */
    public function update_rates()
    {
        $this->update_car_and_hotel_rates();
    }
    
    /**
	 * Generates an array of calendar dates to retrive information for formatted mm/dd/yyyy
	 * Uses instance variable $daysToRetrieve to determine how many dates to generate
	 * @return array calendar dates
	 */
    private function generate_query_dates()
    {
        $dates = array();
        $daysToRetrieve = $this->daysToRetrieve;
		
        for( $i=0; $i<$daysToRetrieve; $i++){
            $dates[] = date('m/d/Y', strtotime('+'.$i.'days')); 
        }
        return $dates; 
    }
    
	/**
	 * Loops through locations, calls query method, and then calls db update method
	 * 
	 * Loops though all locations, and for each query date set when the class was initialized, queries API for car rate, updates database, queries API for hotel rate, and updates database.
	 * This takes a long time because of API call limits. 
	 * @deprecated For now the update script is setup the way it is so that the most current rates get pushed forward as soon as they're available, it
	 * isn't ideal, but some larger decisions about the systems behavior need to be made before this gets addressed.
	 */
	private function update_car_and_hotel_rates()
	{
		$airports = $this->locations;
		$dates = $this->queryDates;
		
	    $updateDayNumber = 0;
        
	    //foreach date
	    foreach ($dates as $startDate) {
	    
            //foreach airport    
            foreach ($airports as $airport => $airportData) {
                //query car rate
                $carRate = $this->get_car_rate($airportData['airport_code'], $startDate);
                        
                //save car rate to database
                $this->dataHandler->update_carhotel_rate($airportData['airport_id'], 'car', $updateDayNumber, $carRate);

                //query hotel rate
                $hotelRates = $this->get_hotel_rate($airportData['airport_code'], $startDate);
                
                //save hotel rate to database
                $this->dataHandler->update_carhotel_rate($airportData['airport_id'], 'hotel', $updateDayNumber, $hotelRates['min'], $hotelRates['max']);   
            }
            $updateDayNumber++;
	    }
    }
    
	/**
	 * Queries API for a car rental rate at a given location for a given date
	 * 
	 * Hotwire returns lots of information, we just take the best deal, since rates for better vehicles tend to only go up by a few dollars a day
	 * @return float|string car rental price ex. 12.50 on success, char "x" on failure
	 */
    private function get_car_rate($airportCode, $startDate)
    {
    	$apiKey = $this->apiKey;
    	$pickupTime = $this->pickupTime;
		$dropoffTime = $this->dropoffTime;
		
        $endDate = date('m/d/Y',strtotime($startDate . "+1 days"));
            
        // This is the querry to hotwire
        $xml_query_string = 'http://api.hotwire.com/v1/search/car?apikey='.$apiKey.'&dest='.$airportCode.'&startdate='.$startDate.'&enddate='.$endDate.'&pickuptime='.$pickupTime.'&dropofftime='.$dropoffTime;       
    
        // If the returned xml file has a key object value, populate database from the XML
        $parsed_xml = simplexml_load_file($xml_query_string);

        if (isset($parsed_xml->Result->CarResult[1]->TotalPrice) == true) {
    
            $avgcar = $parsed_xml->Result->CarResult[1]->TotalPrice;
            $avgcar = round($avgcar, 2);
            $avgcar = number_format((float)$avgcar, 2, '.', '');
            
            //Just in case I ever wanna add Premium Car data for fun
            //Following to parse Premium Car Data
            //$premium = $parsed_xml->Result->CarResult[5]->TotalPrice / 3;
            //$premium = round($premium, 2);
            //$premium = number_format((float)$premium, 2, '.', '');
            
            sleep($this->callLimitTime);
			
            return $avgcar;   
        
		} else {
            sleep(10);   
            return "x";        
        }
    }
    
	/**
	 * Queries Hotwire for a hotel rate
	 * 
	 * Iterates though all 3 & 3.5 star hotels to determine a minimum stay cost and a maximum stay cost for one night. 
	 * Penalizes Las Vegas Hotels by 20 dollars a night because resort fees there are underreported by Hotwire
	 * 
	 * @return array|string array w/Min & Max rates on success, char "x" on failure
	 */
    private function get_hotel_rate($airportCode, $startDate)
    {
    	$apiKey = $this->apiKey;
        $rooms = 1;
        $adults = 1;
        $children = 0;
        
        //query requires an end date, we'll set it for tomorrow        
        $endDate = date('m/d/Y',strtotime($startDate . "+1 days"));

        $xml_query_string = 'http://api.hotwire.com/v1/deal/hotel?apikey='.$apiKey.'&dest='.$airportCode.'&rooms='.$rooms.'&adults='.$adults.'&children='.$children.'&startdate='.$startDate.'&enddate='.$endDate;       
        
        $parsed_xml = simplexml_load_file($xml_query_string);   
        
        $rates = array();
        
        $lowStar = 3;
        $highStar = 3.5;
        
        if (isset($parsed_xml->Result->HotelDeal[0])) {
    
            foreach ($parsed_xml->Result->HotelDeal as $key => $values) {
                
                $hotelStar = $values->StarRating[0];
                
                if ($lowStar <= $hotelStar && $hotelStar <= $highStar) {
                    
                    $rate = ($values->Price);
                    
                    //Las Vegas Hotels, on average, underreport an average of 20 dollars for each day of the bill. The unreported charge is in "resort fees".
                    if ($airportCode == 'LAS') {
                        $rate += 20;
                    }
		 
                    $rates[] = $rate;
                }
            }

            sleep($this->callLimitTime);

            if (empty($rates)) {
                
                $returnRates['min'] = 'no 3 stars';
                $returnRates['max'] = 'no 3 stars';
                
                return $returnRates;
            
            } else {
                $min = intval(min($rates));
                $max = intval(max($rates));
                
                $returnRates = array();
                $returnRates['min'] = $min;
                $returnRates['max'] = $max;
                return $returnRates;
            }
        
        } else {
            
            sleep(10);
			$failedRates = array();
            $failedRates['min'] = 'x';
            $failedRates['max'] = 'x';
			return $failedRates;
        }
    }
}
