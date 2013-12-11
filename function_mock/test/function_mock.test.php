<?php

require_once '../function_mock.php';

class FunctionMockTest extends PHPUnit_Framework_TestCase
{

  public function testCreateMockFunctionDefinitions() {
    // Generate a stub method dynamically and ensure you can call it.
    $result = FunctionMock::createMockFunctionDefinitions(array('test_method1', 'test_method2'));

    $this->assertEquals('function test_method1() { return FunctionMock::getStubbedValue(__FUNCTION__, count(func_get_args()) > 0 ? func_get_args() : NULL); } ' . 
      'function test_method2() { return FunctionMock::getStubbedValue(__FUNCTION__, count(func_get_args()) > 0 ? func_get_args() : NULL); } ', $result);

    FunctionMock::stub('test_method1', 3);

    $this->assertEquals(3, test_method1());
  }

  public function testStub() {
    // Set up the test input.
    $testStubValue = 3;
    $functionName = 'testStub';

    FunctionMock::createMockFunctionDefinitions(array($functionName));
    FunctionMock::stub($functionName, $testStubValue);

    // Check that it sets it correctly.
    $this->assertEquals($testStubValue, testStub());    
  }

  public function testGetStubbedValueForStub() {
    $testStubValue = 3;
    $functionName = 'test';

    FunctionMock::stub($functionName, $testStubValue);

    $actualResult = FunctionMock::getStubbedValue($functionName);

    $this->assertEquals($testStubValue, $actualResult);
  }

  public function testStubWithParameters() {
    // Set up the test input.
    $testStubValue = 3;
    $testStubValueWithParam = 5;
    $functionName = 'testStubParams';
    $paramList = array('param1', 'param2');

    FunctionMock::createMockFunctionDefinitions(array($functionName));
    FunctionMock::stub($functionName, $testStubValue);
    FunctionMock::stub($functionName, $testStubValueWithParam, $paramList);

    // Check that it sets it correctly.
    $this->assertEquals($testStubValue, testStubParams());    
    $this->assertEquals($testStubValueWithParam, testStubParams('param1', 'param2'));    
  }

  /**
   * @expectedException        StubMissingException
   * @expectedExceptionMessage nonExistentStub has not been stubbed yet.
   */
  public function testGetStubbedValueForNonExistentMock() {
    $actualResult = FunctionMock::getStubbedValue('nonExistentStub');
  }

  public function testGetUniqueFunctions() {
    $result = FunctionMock::findFunctionsNeedingMocks(array('./test_php.php'));

    $this->assertEquals(7, count($result));
  }

  public function testGenerateMockFunctions() {
    // Generate a stub method dynamically and ensure you can call it.
    FunctionMock::generateMockFunctions(array('./test_php.php'));

    FunctionMock::stub('abc', 3);

    $this->assertEquals(3, abc('http://abc.com/get'));
  }

  public function testResetStubs() {
    // Stub a few functions, then reset them and see that nothing shows up.
    $testStubValue = 3;
    $functionName = 'testResetStub';

    FunctionMock::createMockFunctionDefinitions(array($functionName));
    FunctionMock::stub($functionName, $testStubValue);

    // Test first that the stub exists and works.
    $this->assertEquals($testStubValue, testResetStub());

    // Then, reset the stubs and make sure it doesn't return anything.
    FunctionMock::resetStubs();

    // Test first that the stub exists and works.
    try {
      testResetStub();
      $this->fail('Should have thrown a StubMissingException');
    } catch (StubMissingException $e) {
      // Expected behavior.
      return;
    }
  }

  public function testDrupalBlockModuleFunctions() {
    // Generate a stub method dynamically and ensure you can call it.
    $result = FunctionMock::generateMockFunctions(array('./block.module'));

    // The concept here is to assume that all the Drupal based functions work 
    // as intended, and simply control the return values to what you'd expect
    // in your test scenario. This gives you control to test different scenarios
    // and verify the results.
    $page = array();
    global $theme;

    $theme = 'abc';

    FunctionMock::stub('drupal_theme_initialize', '');
    FunctionMock::stub('system_region_list', array());
    FunctionMock::stub('menu_get_item', array('path' => 'abc'));
    FunctionMock::stub('drupal_static_reset', '');

    block_page_build($page);

    // $this->assertEquals(3, drupal_http_request('http://abc.com/get'));

  }

}
?>