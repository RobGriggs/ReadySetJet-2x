<?php
/**
 * Holds Weather API Handling Class
 * @author Rob Griggs
 */

/*
 * Weather data is provided by Weather Underground API
 * http://www.wunderground.com/weather/api/
 * 
 * Call Limit: 500 calls a day
 * Call Speedlimit: 10 calls a minuet
 */ 
class WeatherAPI
{
	/**
	 * @var string $apiKey Unique key used to authenticate with API server
	 * @var array $airports Each array key holds a sub array that contains airport_id from database and respective airport_code 
	 * @var DataHandler $dataHandler provides database query and update services
	 * @var int $apiCallLimitTime Time, in seconds, to pass between successful API calls, in order to avoid exceeding API call speedlimit
	 */
	
    private $apiKey,
    		$airports = array(), 
			$dataHandler,
    		$queryDates,
			$apiCallLimitTime = 10;
	
	/**
	 * Initialize Class
	 * @var string $apiKey authentication key for calling weather underground API 
	 */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->dataHandler = Registry::get('DataHandler');
        $this->airports= $this->dataHandler->get_airports();
    }
	
	/**
	 * Call to initiate update update of weather information in database
	 */
	public function update_forecasts()
	{
	    $time = microtime(true);
        
        foreach ($this->airports as $key => $values) {
            
            $airportCode = $values['airport_code'];
            
            $rawWeatherData = $this->get_weather_forecast($airportCode);
            $preparedWeatherData = $this->parse_weather_forecast($rawWeatherData);
            $this->update_database($airportCode, $preparedWeatherData );     
        }
        
        $timeNow = (microtime(true));
        $timePassed = $timeNow - $time;
        echo "finished weather update in: ".$timePassed.' seconds';   
	}
	
	/**
	 * Get an array of airport_codes from the DB, along with their respective database ids
	 * @return array airport data
	 */
	private function get_airports()
	{
        return $this->dataHandler->get_airports();
	}

	/**
	 * Builds query to API and attempts to retrive data from API provider
	 * @var string $airportCode 3 char long airport code ex: "LAX"
	 * @return JSON|false JSON object on success, false on failure
	 */
	private function get_weather_forecast($airportCode) 
	{
		$jsonString = file_get_contents('http://api.wunderground.com/api/'.$this->apiKey.'/forecast10day/q/'.$airportCode.'.json');
		$decodedJSON = json_decode($jsonString);
		
		// If key JSON object is present and has a value, get weather data from the JSON file
		if (isset($decodedJSON->{'forecast'}->{'simpleforecast'}->forecastday[0]->{'date'}->{'weekday_short'}) == true) {
			
            sleep($this->apiCallLimitTime);
            return $decodedJSON;		
        } else {      
            sleep($this->apiCallLimitTime+5);
            return false;     
        }
    }
    
	/**
	 * Parses JSON file from API provider into format required by database update function
	 * @param JSON $data JSON|false JSON Object with weather data for airport, false if no data was retrived
	 */
    private function parse_weather_forecast($data)
    {
        if ($data == false) {
            return false;
        } 
        
        $weather = $data->forecast;
        
        $dayNumber = 0;
        $evenOdd = 2;
        $parsedWeather = array();
        
        //instead of returning a 10 element array with sub arrays for day and night, the api retuns a 20 element array with 2 elements for each day. kinda weird. 
        foreach ($weather->txt_forecast->forecastday as $key => $values) {
            
            //we only want the weather icon from every other element, even elements are day icons, odd elements have night icons
            if ($evenOdd % 2 == 0) {
                $parsedWeather[$dayNumber]['icon_url'] = $values->icon_url;
                $parsedWeather[$dayNumber]['daytime_forecast'] = $values->fcttext;
                $evenOdd++;
            } else {
                $parsedWeather[$dayNumber]['evening_forecast'] = $values->fcttext;
                $evenOdd++;
                $dayNumber++;
            }
        }
            
        $dayNumber = 0;
        
        //additional forecast data is returned in a second array, for the sake of simlicity we iterate though it separately
        foreach ($weather->simpleforecast->forecastday as $key => $values) {
            $parsedWeather[$dayNumber]['conditions'] = $values->conditions;
            $parsedWeather[$dayNumber]['avg_wind'] = $values->avewind->mph;
            $parsedWeather[$dayNumber]['high'] = $values->high->fahrenheit;
            $parsedWeather[$dayNumber]['low'] = $values->low->fahrenheit;
            $dayNumber++;
        }
        
        return $parsedWeather;
    }

	/**
	 * Update weather forecast / Store in DB
	 * @var string $airportCode 3 char long airport code ex: "LAX"
	 * @var array prepared weather data, see parse_weather_forecaset method
	 */
    private function update_database($airportCode, $preparedWeatherData)
    {
        print_r($airportCode).PHP_EOL.PHP_EOL;
        print_r($preparedWeatherData);
        
        $this->dataHandler->update_ten_day_forecast($airportCode, $preparedWeatherData);
    }

}

