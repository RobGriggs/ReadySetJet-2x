$(document).ready(function(){

	//global variables
	var globalData; //results returned from database via ajax call
	var globalDates; //calendar dates associated with information pulled from database
	var globalStartInt; //for use with slider output and sorting, simple integer to date conversion
	var globalEndInt; //for use with slider output and sorting, simple integer to date conversion

	// Buttons & Sliders

	//initial view selector button
    $( ".actionButton" ).click(function(){
   	  var param = $(this).attr("id");
	  getData(param);
	  console.log("clicked "+param);
	});
	
	// initiate resort of data within view
	$( ".update-button" ).click(function(){
		
		var includeCar = $("#car_checkbox").prop("checked");
		var includeHotel = $("#hotel_checkbox").prop("checked"); 
		var startDayNumber = globalStartInt;
		var numberOfNights = globalEndInt - globalStartInt;
		
		//console.log(startDayNumber[1]+' '+numberOfNights[1]);
        resortBudget(startDayNumber, numberOfNights, globalData, includeCar, includeHotel);
    });

	// UI date / calender slider that helps with data filtering and sorting, fires up after initial view selection by user
	function initilizeSlider(){
	    $(function(dates) {

	        $( "#slider-range" ).slider({
				
	        	range: true,
	        	min: 0,
	        	max: 9,
	        	step: 1,
	        	values: [ 0, 2 ],
	        	slide: function( event, ui ) {
	        		
	        		var start = 'day_long_' + ui.values[0];
	        		var end = 'day_long_' + ui.values[1];

	        		globalStartInt = toInteger(ui.values[0]);
	        		globalEndInt = toInteger(ui.values[1]);

	        		// Caching jQuery objects
					//var $element = $(element).text(ui.value);
					//var $container = $element.parent();

					// Calculating new position
					//var newpos = event.pageX - $element.width() / 2;
					//newpos = Math.max(newpos, 0);
					//newpos = Math.min(newpos, $container.innerWidth() - $element.outerWidth());

					//$element.css('left', newpos);
	        		
	            	//$( "#amount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
	          		$("#amount").val( globalDates[start] + " - " + globalDates[end]);

	          	}
	        });

	        //output at page rendering
	        $( "#amount" ).val( globalDates['day_long_0'] + ' - ' + globalDates['day_long_2'] );

			//manually set slider postition var
	        globalStartInt = 0;
	        globalEndInt = 2;


	    });		
	}

	// Ajax & Data Request, requests data from backend to populate view
	function getData(viewRequestType){
		
  		$.ajax({      
			type : "POST",                
			url: "ajax/ajaxController.php",          
			data : { query : viewRequestType},                        
			dataType: 'json',
			timeout: 15000,             
			success: function(data){
			    
			    //first part of return data, calendar dates associated hotel, car, and weather data
			    globalDates = data[0];

			    //weather, car rental rates, and hotel rates associated with major airports
			    globalData = data[1];

			    initilizeSlider();
			    console.log("viewified data");
				viewifyReturnData(data[1], viewRequestType);
			} 
		});  
	}
	
	// View Control / Dispatch
	function viewifyReturnData(data, viewRequestType){
		
		if (viewRequestType == 'budget') {
			renderNewBudget(data, 0);
			console.log('renderNewBudget');
		}
		
		if (viewRequestType == 'activity') {
		
		}
		
		if (viewRequestType == 'weather') {
			renderOutput(JSON.stringify(data));
		}
    }   


    // View redering behavior
    function renderNewBudget(jsonData, day){
    
        $('.coreBox').fadeOut(function() {
            $(this).attr("class","dataCore").fadeIn();
            $('.filter-controls').attr("display","block").fadeIn();
            
            console.log('renderBudget');
			renderBudget(jsonData, day, 3);
        });
    }

    function renderResortedBudget(jsonData, startDay, numDays){
    	
    	$('.dataCore').fadeOut(function() {
            $(this).attr("class","dataCore").fadeIn();
            
			renderBudget(jsonData, startDay, numDays);
        });
    }

    function renderOutput(preformattedOutput){	
    	$(".dataCore").empty().append(preformattedOutput);
	}


    // View rendering, output
    function renderBudget(jsonData, startDayNumber, numberOfNights){
    	    
    	    var output = ''; 
			$subI = 0;
			
			//each price bracket is a JSON object
            $.each(jsonData, function(i, item) {
            
            	//each bracket contains any number of trips in that price bracket
            	for(var x=0; x<item.length; x++) {
			        output += ''  
			        + '<div class="result-container container">'
                        
                        + '<div class="">'  
                            + prepareLocationOutput(item[x])
                            + prepareScoreOutput(item[x], 'budget')
             			+ '</div>'
                        
                        +'<div class="weather-container row">'
                            + prepareWeatherOutput(item[x], startDayNumber, numberOfNights)
                        +'</div>'
                        
                        + '<div class="">'  
                            + prepareCarHotelOutput(item[x], startDayNumber, numberOfNights)
                        + '</div>'
                        
                    + '</div>'
                    + '<br><br>';	                
	                
	                $subI++;
	            }
	             
	            //return $subI < 100;   
			});
            renderOutput(output);
            return;
    }

    function prepareLocationOutput(item){
    	var output = '';
    	output += '<div class="location">'
    			+ item['city'] + ', ' + item['state']
    			+ '</div>';
    	return output;
    }

    function prepareScoreOutput(item, viewRequestType){
    		
    	if (viewRequestType == 'budget') {
    	var output = '<div class="sortscore">';
    		output += '$'
    			   + item['sort_weight']+'</div>';
    	}
    	return output;	
    }
        
    function prepareWeatherOutput(object, sDay, numNights){
    
    	var weatherDayIndex = sDay;


    	//at least one day of weather will be reported. 
    	if (numNights == 0) {
    		numNights = 1;
    	}
    	
    	var output = '';
		
    	for(var x=0; x<numNights; x++) {
    		output += '<div class="weather-smallday col-md-1">'
    				+ globalDates['day_abrv_' + toInteger(x + weatherDayIndex)]
    				+ '<br>'
	    			+ object['high_'+ toInteger(x + weatherDayIndex)]  + ' | ' + object['low_'+ toInteger(x+weatherDayIndex)]
	    			+ '<img src="' + object['icon_url_'+toInteger(x + weatherDayIndex)] + '">'
	    			+ '</div>';
    	}
    	return output;    	
    }

    function prepareCarHotelOutput(item, sDay, numDays){
    	
    	var output = '<div class="budget-breakdown-container">'
    				+'<p>Budget Breakdown <button>V</button></p>' 
    				+'<div class="budget-breakdown">';
    	
    	for (var x=sDay; x<numDays; x++) {
    		
    		if (x < 1) {
    			output += '<p>car: '+ item['car_'+x] + '</p>';
    		}
    		
    		output += '<p>hotel: '+ item['hotel_low_'+x] + '</p>';
    	}
    	
    	output += '</div></div>';
    	return output;    	
    }
    
    function parepareAirportOutput(item){
    	var output = '';
    	output += '<div class="airport-info">'
    	+ '<h1>' + item['airport_code'] + '</h1>'
    	+ '</div>';
    	return output;
    }

	// Sorting Functions
    function resortBudget(startDayNumber, numberOfNights, globalData, includeCar, includeHotel){
        
        var unsortedBudgetData = globalData;
        var sortedBudgetData = {};
        
		//each price bracket is a JSON object
        $.each(unsortedBudgetData, function(i, item) {
        
        	//each bracket contains any number of trips in that price bracket
        	for(var x=0; x<item.length; x++) {

				var carCost = 0;

				if (includeCar === true){
					if (item[x]['car_' + startDayNumber] == 'x' ){         
		                carCost = 'unavailable';   
		            } else {
		            	
		            	var rentalMultiplier;
		            	
		            	if (numberOfNights == 0){
		            		rentalMultiplier = 1; //car rental agency always bills minimum 1 day
		            	} else {
		            		rentalMultiplier = numberOfNights;
		            	}
		            			            		
		            	carCost = item[x]['car_' + startDayNumber] * rentalMultiplier; 	
		            }	
				} 

	            var hotelCost = 0;
				var hotelDayIndex = startDayNumber;
	            if (includeHotel === true ){
		            for (var i = 0; i < numberOfNights; i++) {
		                
		                var currentCol = 'hotel_low_'+ toInteger(hotelDayIndex + i);
		                
		                if( item[x][currentCol] == "x") {
		                        
		                    hotelCost = 'unavailable';    
		                    break;
		                }
		                       
		                hotelCost += parseFloat(item[x][currentCol]);  
	            	}
            	}
	            
	            if (carCost === 'unavailable' || hotelCost === 'unavailable') {
	               var totalCost = 'unavailable';
	            } else {
	                var totalCost = toInteger(carCost + hotelCost);            
	            }
	            
	            item[x]['sort_weight'] = totalCost;
	            
	            if(sortedBudgetData[totalCost] === undefined){
	         		sortedBudgetData[totalCost] = [];	
				}
	            
	            sortedBudgetData[totalCost].push(item[x]);  
            }
		});
            
        var numberOfDays = toInteger(numberOfNights) + 1; 
        
	    renderResortedBudget(sortedBudgetData, startDayNumber, numberOfDays);        
    }
    

    // General Functions
	function toInteger(number){ 
	  return Math.round(  // round to nearest integer
	    Number(number)    // type cast your input
	  ); 
	};
    
});