<?php

class ViewFilter
{
    private $whitelist = array( 'budget' => 'BudgetView',
                                'activity' => 'ActivityView',
                                'weather' => 'WeatherView' );
    
    private $qualifiedViewName = false;                             
                                
    function __construct($requestedView)
    {
        if(isset($this->whitelist[$requestedView])){
            
            $this->qualifiedViewName =  $this->whitelist[$requestedView];
        
        }else{
            
            $ip = $_SERVER['REMOTE_ADDR'];
            $dateTime = date("Y-m-d H:i:s");
            $msg = 'illegal request: "'.$requestedView.'" from: '.$ip.' on: '.$dateTime;  
            
            throw new Exception($msg, 1);    
        }  
    }
    
    public function get_qualifed_view_name()
    {
        return $this->qualifiedViewName;
    }
                         
}