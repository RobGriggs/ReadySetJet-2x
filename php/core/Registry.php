<?php
/**
 * Holds Regiestry Class
 */
 
 /**
  * A simple key => value regiestry for important objects
  */
class Registry
{
	
	/**@var self singleton instance*/
    private static $instance = null;
	
	/**@var array Objects registered*/
    private $globalObjects = array();
    
	/**Initialize, private to support singleton */
    private function __construct(){}
    
	/**
	 * Initilizes or returns singleton of Registry class 
	 */
    public static function get_instance()
    {
        if( self::$instance === null ){
            self::$instance = new self();
            return self::$instance;
        }else{
            return self::$instance;
        }
    }
    
	/**
	 * Sets an object in registry
	 */
    public function set( $key, $value )
    {
        $this->globalObjects[$key] = $value;
    }
    
	/**
	 * gets an object from registry, static class uses instantiated class method get_local()
	 * to allow a static get() method call
	 */
    public static function get($key){
        return self::$instance->get_local($key);
    }
    
	/**
	 * Gets object from registry
	 */
    private function get_local( $key )
    {
        if(isset( $this->globalObjects[$key] ) ){
            return $this->globalObjects[$key];
        }
        return null;       
    }
}