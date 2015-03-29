<?php

class ViewConstructor
{
    private $viewObject; 
    
    function __construct($requestedView)
    {
        $filteredViewName = $this->filter_view($requestedView);
        $viewObject = $this->build_view($filteredViewName);
        $this->viewObject = $viewObject;
    }
    
    private function filter_view($requestedView)
    {
        $viewFilter = new ViewFilter($requestedView);
        return $viewFilter->get_qualifed_view_name();
    }   
    
    private function build_view($viewName)
    {
        $viewObject = new $viewName();
        return $viewObject;
    }
    
    public function get_view()
    {
        $this->viewObject;
    }
}