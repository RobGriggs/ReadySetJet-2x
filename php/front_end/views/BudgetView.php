<?php

class BudgetView implements View
{
    private $dataHandler;
    
    function __construct(){
        $dataHandler = Registry::get('DataHandler');
        $this->dataHandler = $dataHandler;
        $this->build_view();
    }

    private function build_view()
    {
        $carHotelData = $this->dataHandler->get_carhotel_data();
        
        $unsortedBudgetData = $this->dataHandler->get_budget_data();
        
        $nights = 2;
        
        $sortedBudgetData = array();
        
        //the default search is going to start on the current day, and include two nights in the hotel and 3 days of car rental
        foreach ($unsortedBudgetData as $key => $vals) {
            
            if ($vals['car_0'] == 'x' ){         
                $carCost = 'unavailable';   
            } else {
                $carCost = $vals['car_0'] * ($nights); 
            }
                   
            $hotelCost = 0;
            
            //assuming that you're there for three days, you'll probably only need the hotel two nights
            //remember, database is 0 indexed.            
            for ($i = 0; $i < $nights; $i++) {
                
                $currentCol = 'hotel_low_'.$i;
                
                if( $vals[$currentCol] == "x") {
                        
                    $hotelCost = 'unavailable';    
                    break;
                }
                       
                $hotelCost += $vals[$currentCol];  
            }
            
            if ($carCost == 'unavailable' OR $hotelCost == 'unavailable') {
                $totalCost = 'unavailable';
            } else {
                $totalCost = $carCost + $hotelCost;            
                $totalCost = round($totalCost, 0, PHP_ROUND_HALF_UP); 
            }
            $vals['sort_weight'] = $totalCost;
            $sortedBudgetData[$totalCost][] = $vals;   
						
        }
        ksort($sortedBudgetData, SORT_NATURAL);
        
		//shoe in the dates we're working with
	    for( $i=0; $i<=9; $i++){
	        $dates['day_abrv_'.$i] = date('D', strtotime('+'.$i.'days'));
	    	$dates['day_numeric_'.$i] = date('m/d', strtotime('+'.$i.'days')); 
            $dates['day_long_'.$i] = date('D m/d', strtoTime('+'.$i.'days'));
	    }
		
		$returnData = array( $dates, $sortedBudgetData );
		
        $returnJSON = json_encode($returnData);
        
        echo $returnJSON;
    }
}
 