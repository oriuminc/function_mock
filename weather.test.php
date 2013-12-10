<?php

require_once '../modules/custom/weather/weather.module';
require_once '../test/function_mock/function_mock.php';

class WeatherTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        FunctionMock::generateStubbableFunctions(array('../modules/custom/weather/weather.module'));
    }

    public function testWeatherGetWeatherData()
    {
        $testJsonResponse = (object) array ('data' => 'abc');
        $testJsonResponse->data = 'abc';

        // setDefaultStubValue('drupal_http_request');

        FunctionMock::stub('drupal_http_request', $testJsonResponse);
        FunctionMock::stub('drupal_json_decode', $testJsonResponse->data);

        $result = weather_get_weather_data();

        $this->assertEquals($testJsonResponse->data, $result);
    }

    public function __destruct()
    {
        FunctionMock::resetStubs();
    }    
}
?>