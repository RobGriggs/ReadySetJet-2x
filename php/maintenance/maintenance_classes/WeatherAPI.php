<?php

/*
 * Weather data is provided by Weather Underground API
 * http://www.wunderground.com/weather/api/
 * 
 * Call Limit: 500 calls a day
 * Call Speedlimit: 10 calls a minuet
 */
 
class WeatherAPI
{
    private $apiKey;
    private $airports = array(); 
	private $dataHandler;
    private $queryDates;
	private $apiCallLimitTime = 10; //seconds for rest
	
	//call limit time, to be set as long, or as longer than the allowed time between API calls 
	private $weatherAPICallLimitTime = 12; //seconds
	
    function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->dataHandler = Registry::get('DataHandler');
        $this->airports= $this->dataHandler->get_airports();
        
        //for debugging
        //$this->airports = array( array( 'airport_id' =>'78', 'airport_code' => 'HNL'));
    }
	
	public function update_forecasts()
	{
	    $time = microtime(true);
        
        foreach ( $this->airports as $key => $values) {
            
            $airportCode = $values['airport_code'];
            
            $rawWeatherData = $this->get_weather_forecast( $this->apiKey, $airportCode, $this->weatherAPICallLimitTime );
            $preparedWeatherData = $this->parse_weather_forecast( $rawWeatherData );
            $this->update_database( $this->dataHandler, $airportCode, $preparedWeatherData );
            
        }
        
        $timeNow = (microtime(true));
        $timePassed = $timeNow - $time;
        echo "finished weather update in: ".$timePassed.' seconds';
        
	}
	
	//get a list of airports from the DB
	private function get_airports()
	{
        return $this->dataHandler->get_airports();
	}

    private function update_database( $dataHandler, $airportCode, $preparedWeatherData )
    {
        print_r($airportCode).PHP_EOL.PHP_EOL;
        print_r($preparedWeatherData);
        
        $dataHandler->update_ten_day_forecast($airportCode, $preparedWeatherData);
    }

    //retrieve a weather forecast based on an airport location
	private function get_weather_forecast($apiKey, $airport, $apiCallLimitTime) 
	{
		$jsonString = file_get_contents('http://api.wunderground.com/api/'.$apiKey.'/forecast10day/q/'.$airport.'.json');
		$decodedJSON = json_decode($jsonString);
		
		// If key JSON object is present and has a value, get weather data from the JSON file
		if (isset($decodedJSON->{'forecast'}->{'simpleforecast'}->forecastday[0]->{'date'}->{'weekday_short'}) == true) {
			
            sleep($apiCallLimitTime);
            return $decodedJSON;
            			
        } else {
            
            sleep($apiCallLimitTime+5);
            return false;     
        }
    }
    
    //extract important data from weather forecast data
    private function parse_weather_forecast( $data )
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

}

