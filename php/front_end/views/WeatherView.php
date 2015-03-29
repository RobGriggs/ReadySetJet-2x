<?php

class WeatherView
{
    function __construct()
    {
        $this->dataHandler = Registry::get('DataHandler');
        $this->build_view();  
    }
    
    private function build_view()
    {
        
        $unsortedData = $this->dataHandler->get_weather_data();
        
        $defaultNumOfDays = 3;
        
        $rankingContainer = array();
        $sortedData = array();
        
        //the default search is going to start on the current day, and include two nights in the hotel and 3 days of car rental
        foreach ($unsortedData as $key => $vals) {
            
            $total = 0;
            //average high over three days           
            for ($i = 0; $i < $defaultNumOfDays; $i++) {
                
                $currentCol = 'high_'.$i;
                
                if( $vals[$currentCol] == 'x') {
                    $total = 'unavailable';    
                    break;
                }
                       
                $total += $vals[$currentCol];  
            }
             
            if ($total == 'unavailable') {
                $avgHigh = 'unavailable';
            } else {
                $avgHigh = $total / $defaultNumOfDays;
            }
            
            $score = 20;
            
            //build a conditions score based on the conditions each day
            for ($i = 0; $i < $defaultNumOfDays; $i++) {
                
                $currentCol = 'conditions_'.$i;
                
                switch ($vals[$currentCol]) {
                    case 'Clear':
                        $score += 3;
                        break;
                    case 'Partly Cloudy':
                        $score += 2;
                        break;
                    case 'Mostly Cloudy':
                        $score += 1;
                        break;
                    case 'Overcast':
                        //an overcast day is still better than rain, snow, storms...ect;
                        break;
                    case 'x':
                        $score = 'unavailable';
                        break 2;
                    default:
                        // -2 because we don't want one really nice day, like a clear day after a series of storms, to make up for two bad days.
                        $score -= 2;
                }
            }
            
            if ($avgHigh == 'unavailable' OR $score == 'unavailable') {
                $ranking = 'unavailable';
                //$rankingContainer['rank unavailable'][] = $vals;
            } else {

                $rankingContainer[$score][$avgHigh][] = $vals['airport_code'];

            }  
        }
        krsort($rankingContainer, SORT_NATURAL);
        
        foreach ($rankingContainer as &$subContainer) {

            krsort($subContainer, SORT_NATURAL);
        }
        
        $sortSave = array();
        $returnJSON = json_encode($rankingContainer);
        
        echo $returnJSON;
    }
}

