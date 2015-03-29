<?php
require_once('../core/initialize_application.php');

$carHotel = new CarAndHotelAPI($hotwireAPIKey);
$carHotel->update_rates();

$weather = new WeatherAPI($weatherAPIKey);
$weather->update_forecasts();

//$images = new ImageAPI($microsoftAPIKey);
//$images->update_activity_images();