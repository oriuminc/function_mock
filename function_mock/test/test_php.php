<?php

/**
 * Implements hook_block_info().
 */
function weather_block_info() {
	$blocks['weather'] = array(
		'info' => t('Weather'),
	);
	return $blocks;	
}

/**
 * Implements hook_block_view().
 */
function weather_block_view($delta = '') {
	// This example is adapted from node.module.
	$block = array();

	//This is a constant which will be used to convert the temperature from Kelvin scale to Celcius scale
	define('KELVIN_IN_ZERO_CELSIUS', 273.15);


	// Check to make sure we're modifying  "weather" block.
	switch ($delta) {
		case 'weather':

		  // Set our "Subject" for the block.
		  $block['subject'] = t('Weather report');

		  // Get the weather data.       
		  $weather_data = weather_get_weather_data();		
		  $celsius = $weather_data['main']['temp'] - KELVIN_IN_ZERO_CELSIUS;
		  $element = $weather_data['weather'][0]['main'];
		  
		  // Build our message.
		  $message = "It's " . $celsius . " outside. And " . $element;

		  // Set our message to the "Content" for the block.
		  $block['content'] = $message;			
		  break;
	}
	// Return our  message to the block.
	return $block;		
}

/**
 * Get weather data.
 */
function weather_get_weather_data() {
	$api_url = 'http://api.openweathermap.org/data/2.5/weather?q=Toronto,ca';
	// Capturing the JSON data from the weather API.
	$json = drupal_http_request($api_url);

	// Decoding the JSON DATA into associative array.
	$result = drupal_json_decode($json->data);

	// Return the array to weather_get_weather_data function.
	return $result;
}
