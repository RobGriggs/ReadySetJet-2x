<?php

class Registry
{
    private static $instance = null;
    private $globalObjects = array();
    
    private function __construct(){}
    
    public static function get_instance()
    {
        if( self::$instance === null ){
            self::$instance = new self();
            return self::$instance;
        }else{
            return self::$instance;
        }
    }
    
    public function set( $key, $value )
    {
        $this->globalObjects[$key] = $value;
    }
    
    public static function get($key){
        return self::$instance->get_local($key);
    }
    
    private function get_local( $key )
    {
        if(isset( $this->globalObjects[$key] ) ){
            return $this->globalObjects[$key];
        }
        return null;       
    }
}