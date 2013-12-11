<?php

define('TOKEN_CODE', 0);
define('TOKEN_VALUE', 1);
define('SPACE_CODE', 371);

define('DEFAULT_STUB_VALUE', 'default_stub_value');
define('NO_TOKEN_FOUND_CODE', -999);

/**
 * Class that supports stubbing functions.
 */
class FunctionMock
{
  private static $stubFunctionList = array();

  /**
   * Returns a stubbed value based on the function name.
   *
   * @param $functionName
   *   The name of the function to retrieve the stubbed value from.
   * @return
   *   The stubbed value for the function, if it exists. Otherwise it
   *   will throw an exception asking a stubbed value to be defined.
   * @throws StubMissingException
   */
  public static function getStubbedValue($functionName, $paramList = NULL) {
    if (array_key_exists($functionName, self::$stubFunctionList)) {
      if (self::paramListSpecificStubExists($functionName, $paramList)) {
        return self::$stubFunctionList[$functionName][serialize($paramList)];
      } else {
        return self::$stubFunctionList[$functionName][DEFAULT_STUB_VALUE];
      }
    } else {
      throw new StubMissingException($functionName);
    } 
  }

  private static function paramListSpecificStubExists($functionName, $paramList) {
    return $paramList !== NULL && array_key_exists(serialize($paramList), self::$stubFunctionList[$functionName]);    
  }

  /** 
   * Sets up a stub value for a given mock function.
   *
   * @param $functionName
   *   The name of the function to retrieve the stubbed value from.
   * @param $returnValue
   *   The value that you want returned when the function is called.
   * @param $paramList
   *   Optional array of parameters you want an exact match on, so you can 
   *   do a conditional stub.
   */
  public static function stub($functionName, $returnValue, $paramList = NULL) {
    if ($paramList !== NULL) {
      // Make a key out of the $paramList array, by simply serializing it into a string.
      self::$stubFunctionList[$functionName][serialize($paramList)] = $returnValue;
    } else {
      self::$stubFunctionList[$functionName][DEFAULT_STUB_VALUE] = $returnValue;
    }
  }

  /**
   * Resets all the stubbed functions.
   */
  public static function resetStubs() {
    // Just empty the array.
    self::$stubFunctionList = array();
  }

  /**
   * Finds the nearest previous token that doesn't contain a space as the value.
   *
   * @param &$tokens
   *   Array of PHP language tokens to search.
   * @param $i
   *   Index to start searching from. The function will search for the next item from this point.
   * @return
   *   The value of the next non space token, or an array with a TOKEN_CODE of NO_TOKEN_FOUND_CODE is
   *   returned.
   */
  private static function findPrevNonSpaceToken(&$tokens, $i) {
    $counter = $i - 1;

    if ($counter <= 0) {
      return array(TOKEN_CODE => NO_TOKEN_FOUND_CODE);
    }

    while ($tokens[$counter][TOKEN_CODE] === SPACE_CODE &&
            $counter >= 0) {
      $counter--;  
    }

    return $tokens[$counter];
  }

  /**
   * Finds the next token that doesn't contain a space as the value.
   *
   * @param &$tokens
   *   Array of PHP language tokens to search.
   * @param $i
   *   Index to start searching from. The function will search for the next item from this point.
   * @return
   *   The value of the next non space token, or an array with a TOKEN_CODE of NO_TOKEN_FOUND_CODE is
   *   returned.
   */
  private static function findNextNonSpaceToken(&$tokens, $i) {
    $counter = $i + 1;    

    if ($counter >= count($tokens)) {
      return array(TOKEN_CODE => NO_TOKEN_FOUND_CODE);
    }

    while ($tokens[$counter][TOKEN_CODE] === SPACE_CODE &&
            $counter < count($tokens)) {
      $counter++;  
    }

    return $tokens[$counter];
  }

  /**
   * Returns the names of all the functions that would require 
   * mocks to be defined (based on a list of source files to search holistically).
   *
   * @param $srcFiles
   *   List of source files to consider. Note: In performing the search these files will be loaded and executed.
   * @return
   *   Array of function names that will need to be mocked.
   */  
  public static function findFunctionsNeedingMocks($srcFiles) {
    // Loop through all the source files to find the ones that need to be searched.
    $completeFile = self::loadFiles($srcFiles);

    // Do a token based search next based on the loaded files, which is closer but picks up some 
    // strings that don't entirely match.
    $tokens = token_get_all($completeFile);

    $result = array();

    for ($i = 0; $i < count($tokens); $i++) {
      $token = $tokens[$i];

      if ($token[TOKEN_CODE] !== T_STRING) {
        continue;
      }

      $nextToken = self::findNextNonSpaceToken($tokens, $i);

      if ($nextToken[TOKEN_CODE] !== '(') {
          // Not a function call.
          continue;
      }

      $prevToken = self::findPrevNonSpaceToken($tokens, $i);

      if ($prevToken[TOKEN_CODE] === NO_TOKEN_FOUND_CODE) {
          // It's a function definition, not a function call.
          continue;
      }

      if ($prevToken[TOKEN_CODE] === T_FUNCTION) {
          // It's a function definition, not a function call.
          continue;
      }

      if ($prevToken[TOKEN_CODE] === T_OBJECT_OPERATOR) {
          // It's a method invocation, not a function call.
          continue;
      }

      if ($prevToken[TOKEN_CODE] === T_DOUBLE_COLON) {
          // It's a static method invocation, not a function call.
          continue;
      }

      if ($prevToken[TOKEN_CODE] === T_NEW) {
          // It's a class instantiation.
          continue;
      }

      // If it gets all the way here, it's a function call.
      $result[] = $token[TOKEN_VALUE];
    }

    // Look for functions that do not exist. These are the ones that need to be mocked.
    $result = array_filter($result, function($function) { return !function_exists(trim($function)); });  

    return $result;
  }

  /**
   * Load and include files into the PHP context.
   *
   * @param $srcFiles
   *   List of source files to load.
   * @return
   *   Combined contents of all the files and their PHP code loaded and executed. Note if any errors occur on
   *   any specific file being loaded, it will be ignored.
   */  
  public static function loadFiles($srcFiles) {
    $result = '';
    foreach ($srcFiles as $srcFile) {
      include_once $srcFile;
      $result .= file_get_contents($srcFile);
    }

    return $result;
  }

  /**
   * Generate actual function definitions that can be stubbed, based on an array of function names.
   *
   * @param $functionList
   *   List of functions to create mocks for.
   * @return
   *   Combined contents of all PHP code for mock functions created based on the function list.
   */  
  public static function createMockFunctionDefinitions($functionList) {
    $functionString = '';
    
    foreach ($functionList as $item) {      
      $newFunctionDefinition = 'function ' . $item . '() { return FunctionMock::getStubbedValue(__FUNCTION__, count(func_get_args()) > 0 ? func_get_args() : NULL); } ';

      if (!function_exists($item)) {
        eval($newFunctionDefinition);
      }

      $functionString .= $newFunctionDefinition;
    }

    return $functionString;
  }

  /**
   * Finds and generates mock functions based on a set of source files provided.
   *
   * Any functions that are not already PHP functions or declared within the scope of 
   * the passed source files list will be auto-generated with mocks.
   *
   * @param $srcFiles
   *   List of source files to consider for creating mock functions.
   * @return
   *   Combined contents of all PHP code for mock functions created based on the function list.
   */ 
  public static function generateMockFunctions($srcFiles) {
    $functionList = self::findFunctionsNeedingMocks($srcFiles);

    return self::createMockFunctionDefinitions($functionList);
  }
}

/**
 * Custom exception for a stub missing.
 */
class StubMissingException extends Exception
{
  public function __construct($functionName, $code = 0, Exception $previous = null) {
      $this->message = $functionName . ' has not been stubbed yet.' . "\r\n" .
        'Please call FunctionMock::stub(\'' . $functionName . '\', <stub_value>) to set a value.';
 
      parent::__construct($this->message, $code, $previous);
  }

  public function __toString() {
    return $this->message;
  }
}

?>