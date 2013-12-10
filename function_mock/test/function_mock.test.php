<?php

require_once '../function_mock.php';

class FunctionMockTest extends PHPUnit_Framework_TestCase
{
  public function testResetStubs() {
    FunctionMock::resetStubs();

    $stubFunctionList = FunctionMock::getStubbedFunctionList();

    // Check that the array has no stubbed values.
    foreach ($stubFunctionList as &$stubFunctionListItem) {
        $this->assertEquals(false, array_key_exists('stub_value', $stubFunctionListItem));
    }        
  }

  public function testStub() {
    // Stub a function and verify it shows what you need.

    // Set up the test input.
    $testStubValue = 3;
    $functionName = 'abc';

    FunctionMock::stub($functionName, $testStubValue);

    // Check that it sets it correctly.
    $stubFunctionList = FunctionMock::getStubbedFunctionList();
    $this->assertEquals($testStubValue, $stubFunctionList[$functionName]['stub_value']);
  }

  public function testGetStubbedValueForStub() {
    $testStubValue = 3;
    $functionName = 'abc';

    FunctionMock::stub($functionName, $testStubValue);

    $actualResult = FunctionMock::getStubbedValue($functionName);

    $this->assertEquals($testStubValue, $actualResult);
  }

  /**
   * @expectedException        StubMissingException
   * @expectedExceptionMessage nonExistentStub has not been stubbed yet.
   */
  public function testGetStubbedValueForNonExistentStub() {
    $actualResult = FunctionMock::getStubbedValue('nonExistentStub');

    $this->assertEquals($testStubValue, $actualResult);
  }

  public function testGetUniqueFunctions() {
    $result = FunctionMock::findFunctionsNeedingStubs(array('./test_php.php'));

    $this->assertEquals(7, count($result));
  }

  public function testCreateStubbableFunctionDefinitions() {
    // Generate a stub method dynamically and ensure you can call it.
    $result = FunctionMock::createStubbableFunctionDefinitions(array('test_method1', 'test_method2'));

    $this->assertEquals('function test_method1() { return FunctionMock::getStubbedValue(__FUNCTION__); } ' . 
        'function test_method2() { return FunctionMock::getStubbedValue(__FUNCTION__); } ', $result);

    FunctionMock::stub('test_method1', 3);

    $this->assertEquals(3, test_method1());
  }

  public function testGenerateStubbableFunctions() {
    // Generate a stub method dynamically and ensure you can call it.
    FunctionMock::generateStubbableFunctions(array('./test_php.php'));

    FunctionMock::stub('abc', 3);

    $this->assertEquals(3, abc('http://abc.com/get'));
  }

  // public function testDrupalBlockModuleFunctions() {
  //   // Generate a stub method dynamically and ensure you can call it.
  //   $result = FunctionMock::generateStubbableFunctions(array('./block.module'));

  //   $page = array();
  //   block_page_build($page);

  //   print_r($result);

  //   // $this->assertEquals(3, drupal_http_request('http://abc.com/get'));

  // }

}
?>