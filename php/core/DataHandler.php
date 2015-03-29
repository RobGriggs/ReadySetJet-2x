<?php
/**
 * Holds DataHandler class
 * @author Rob Griggs
 */

/**
 * Provides database query and update services to application
 */
class DataHandler
{
	/** @var self|null Instance of DataHandler */
    private static $instance = null;
	
	/** @var PDO $db Connection to application database */
    private $db;
    
	/**
	 * Private, class is singleton
	 * 
	 * @param PDO $dbConnection a PDO object connected to the main application database 
	 */
    private function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }
    
	/**
	 * Returns an instance of the class if it has already been intantiated and stored, otherwise instantiates and stores self
	 * 
	 * @param PDO $dbConnection A PDO connection to the main application database
	 * @return self
	 */
	public static function get_instance(PDO $dbConnection = null)
    {
        if( self::$instance === null ){
            self::$instance = new self($dbConnection);
            return self::$instance;
        }
        return self::$instance;
    }
    
	/**
	 * Provide a list of airport codes and their respective database ids
	 * 
	 * @return array
	 */
    public function get_airports()
    {
        $qry = 'SELECT airport_id, airport_code from airports';
        $stmt = $this->db->prepare($qry);
        $stmt->execute();
        $airports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $airports;
    }
    
	/**
	 * Provides all neccessary data to budget view
	 * 
	 * @return array
	 */
    public function get_budget_data()
    {
        $qry = 'SELECT d.city, e.state, a.*, c.* from carhotel_rates a 
                JOIN airports b
                ON a.airport_id = b.airport_id
                JOIN weather_forecast c
                ON b.airport_code = c.airport_code
                JOIN cities d
                ON b.city_id = d.city_id
                JOIN states e
                ON b.state_id = e.state_id ';

        $stmt = $this->db->prepare($qry);
        $stmt->execute();
        $budgetData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $budgetData;                      
    }
    
	/**
	 * Provides all neccessary data to weather view
	 * 
	 * @return array
	 */
    public function get_weather_data()
    {
        $qry = 'SELECT a.* FROM weather_forecast a
                JOIN airports b
                on a.airport_code = b.airport_code
                JOIN carhotel_rates c
                ON c.airport_id = b.airport_id';

        $stmt = $this->db->prepare($qry);
        $stmt->execute();
        $weatherData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $weatherData;                      
    }

	/**
	 * Returns all data in carhotel_rates table
	 * 
	 * @return array
	 */
    public function get_carhotel_data()
    {
        $qry = 'SELECT * FROM carhotel_rates';
        $stmt = $this->db->prepare($qry);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    
	/**
	 * Returns activites and locations of activities in database
	 * 
	 * @deprecated used only for testing?
	 * 
	 * @return array
	 */
    public function get_activity_data()
    {
        $qry = 'SELECT a.activity_id, b.activity_type, a.activity, c.state FROM activities a
               JOIN activity_types b
               ON a.activity_type_id = b.activity_type_id
               JOIN states c
               ON a.state_id = c.state_id';
               
        $stmt = $this->db->prepare($qry);
        $stmt->execute();
        
        if(!$stmt){
            throw new Exception("Error Retriving Activity Data ".$stmt->errorInfo());
            return false;
        }
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;      
    }
    
    /**
	 * Accepts a car rental or hotel booking rate near a specific location, for one day, and saves said rate to the database
	 * 
	 * @param string $airportID the respective database airport_id associated with the airport that the supplied rates pertain to
	 * @param string $rateType either 'car' or 'hotel'
	 * @param string $updateDayNumber an integer value 0-9, 
	 * @param string $rate1 decimal number as a string
	 * @param string $rate2 deciaml number as a string
	 * 
	 * @return void
	 * 
	 */
    public function update_carhotel_rate($airportID, $rateType, $updateDayNumber, $rate1, $rate2 = null)
    {
        // The DB follows a convention of car1, car1, car3... hotel1, hotel2, hotel3, with the $updateDay being the number associated with the rate on that given day
         
         
        if ($rateType == 'car') {
            $column = $rateType.'_'.$updateDayNumber;
            $qry = 'INSERT INTO carhotel_rates (airport_id, '.$column.') VALUES ('.$airportID.', :rate) ON DUPLICATE KEY UPDATE '.$column.'=VALUES('.$column.')';
            $stmt = $this->db->prepare($qry);
            $stmt->bindParam(':rate', $rate1, PDO::PARAM_STR);  
        } 
        
        if ($rateType == 'hotel'){
            $column1 = $rateType.'_low_'.$updateDayNumber;
            $column2 = $rateType.'_high_'.$updateDayNumber;
            $qry = 'INSERT INTO carhotel_rates (airport_id, '.$column1.','.$column2.') VALUES ('.$airportID.', :rate1, :rate2) ON DUPLICATE KEY UPDATE '.$column1.'=VALUES('.$column1.'), '.$column2.'=VALUES('.$column2.')';
            $stmt = $this->db->prepare($qry);
            $stmt->bindParam(':rate1', $rate1, PDO::PARAM_STR);     
            $stmt->bindParam(':rate2', $rate2, PDO::PARAM_STR);          
        }

        $stmt->execute();  
    }

	/**
	 * Updates the weather database with next ten days of weather
	 * 
	 * @param string $airportCode The airport code, ex "DEN" associated with the weather forecast being supplied
	 * @param array $tenDayArray and array of weather forecast data for the next ten days
	 * 
	 * @return void
	 */
    public function update_ten_day_forecast($airportCode, $tenDayArray)
    {
        
        $forecastGroup = array();
            
        foreach ($tenDayArray as $dayNumber => $value) {
            $forecastGroup['icon_url_'.$dayNumber] = $value['icon_url'];
            $forecastGroup['conditions_'.$dayNumber] = $value['conditions'];
            $forecastGroup['wind_'.$dayNumber] = $value['avg_wind'];
            $forecastGroup['day_forecast_'.$dayNumber] = $value['daytime_forecast'];
            $forecastGroup['evening_forecast_'.$dayNumber] = $value['evening_forecast'];
            $forecastGroup['high_'.$dayNumber] = $value['high'];
            $forecastGroup['low_'.$dayNumber] = $value['low'];
        }
      
        $columns = '';
        $columnValues = '';

        $columns .= 'airport_code'.',';
        $columnValues .= '"'.$airportCode.'",';
        
        $colCount = count($forecastGroup);
        $commaCount = 0;
 
        $onDuplicateKeyString = '';
      
        foreach( $forecastGroup as $column => $value) {
            
            $columns .= $column;
            $columnValues .= '"'.$value.'"';
            $onDuplicateKeyString .= $column.'=VALUES('.$column.')';
            $commaCount++;
            
            if($colCount != $commaCount) {
                $columns .= ',';
                $columnValues .= ',';
                $onDuplicateKeyString .= ',';
            }
        }    
        
        $columns = '('.$columns.')';
        $columnValues = '('.$columnValues.')';
        
        $qry = 'INSERT INTO weather_forecast '.$columns.' VALUES '.$columnValues.' ON DUPLICATE KEY UPDATE '.$onDuplicateKeyString;
        
        $stmt = $this->db->prepare($qry);
        
        print_r($stmt);
        
        $stmt->execute();
    }

	/**
	 * Updates the database image URLs associated with an activity_id
	 * 
	 * @param string $activity_id The database id associated with a particular activity
	 * @param array $images URLs to images associated with an activity
	 * 
	 * @return void
	 */
    public function update_activity_images($activity_id, $images)
    {
        
        $counter = 0;
        $tableColLimit = 15;
        $columns = '';
        $colValues = '';
        $colCount = count($images);
        $commaCount = 0;
        $onDuplicateKeyString = '';
        
        $columns .= 'activity_id'.',';
        $colValues .= '"'.$activity_id.'",';
        
        foreach ($images as $key => $value)
        {
            $columns .= $key;
            $colValues .= '"'.$value.'"';
            $onDuplicateKeyString .= $key.'=VALUES('.$key.')';
            
            $commaCount++;
            
            if($colCount != $commaCount) {
                $columns .= ',';
                $colValues .= ',';
                $onDuplicateKeyString .= ',';
            }
        }
        
        $columns = '('.$columns.')';
        $columnValues = '('.$colValues.')';
        
        $qry = 'INSERT INTO activity_images '.$columns.' VALUES '.$columnValues.' ON DUPLICATE KEY UPDATE '.$onDuplicateKeyString;
        
        $stmt = $this->db->prepare($qry);
        $stmt->execute();            
    }
}
