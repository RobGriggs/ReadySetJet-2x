<?php
/**
 * Holds Viewfilter Class
 */
 
 /**
  * Checks view request against whitelist containted in local variable
  */
class ViewFilter
{
	/** @var array List of key values, a valid request string as the key from the front end and the respective view classname as the value */
    private $whitelist = array( 'budget' => 'BudgetView',
                                'activity' => 'ActivityView',
                                'weather' => 'WeatherView' );
    
	/** @var boolean|string false until set to a qualified class name associated with the request view  */ 
    private $qualifiedViewName = false;                             
    
	/**
	 * Initialize
	 * @param string $requestedView view request from frontend
	 * @return void
	 */                            
    function __construct($requestedView)
    {
        if(isset($this->whitelist[$requestedView])){
            
            $this->qualifiedViewName = $this->whitelist[$requestedView];
        
        }else{
            
            $ip = $_SERVER['REMOTE_ADDR'];
            $dateTime = date("Y-m-d H:i:s");
            $msg = 'illegal request: "'.$requestedView.'" from: '.$ip.' on: '.$dateTime;  
            
            throw new Exception($msg, 1);    
        }  
    }
    
	/**
	 * Returns classname associated with the view requested
	 * @return boolean|string either false or the classname of the associated view
	 */
    public function get_qualifed_view_name()
    {
        return $this->qualifiedViewName;
    }                     
}