<?php
/**
 * Holds view constructor class
 */
 
/**
 * Tools to manage the filtering, construction, and output of a view
 */
class ViewConstructor
{
	/**@var View a view object, pre-formatted for output*/
    private $viewObject; 
	
	/**@var string request from front end for a view return*/
    private $requestedView;
    
	/**
	 * Initialize 
	 * @var string $requestedView a string, representing a view, requested from frontend via an ajax call
	 */
    function __construct($requestedView)
    {
    	$this->requestedView = $requestedView;
        $filteredViewName = $this->filter_view();
        $viewObject = $this->build_view($filteredViewName);
        $this->set_view($viewObject);
    }
    
	/**
	 * Checks the requested view against a whitelist of view names
	 */
    private function filter_view()
    {
		$requestedView = $this->requestedView;
        $viewFilter = new ViewFilter($requestedView);
        return $viewFilter->get_qualifed_view_name();
    }   
    
	/**
	 * Intantiates new view after filtering
	 * 
	 * @var string class name of view
	 */
    private function build_view($filteredViewName)
    {
        $viewObject = new $filteredViewName();
        return $viewObject;
    }
	
	/**
	 * Validates that returned view is of class type view
	 * 
	 * @param View $view setter helps enforce that returned view uses View interface
	 */
	private function set_view(View $view)
	{
		$this->viewObject = $view;
	}
    
	/**
	 * Returns view that has been requested and filtered 
	 *
	 * @return View new view object
	 */
    public function get_view()
    {
        return $this->viewObject;
    }
}