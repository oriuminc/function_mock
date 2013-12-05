<?php

define('FUNCTION_REGEX', '/(\w+)\s*\(.*\)/'); // any string that ends with a "("


/**
 * Custom exception for a stub missing.
 */
class FunctionMock
{
  private static $stubFunctionList = array();

  public static function getStubbedFunctionList() {
    return self::$stubFunctionList;
  }

  /**
   * Return a stubbed value based on the function name.
   *
   * @param $function_name 
   */
  public static function getStubbedValue($function_name) {
    if (array_key_exists($function_name, self::$stubFunctionList)) {
      return self::$stubFunctionList[$function_name]['stub_value'];
    } else {
      throw new StubMissingException($function_name);
    } 
  }


  /** 
   * Stubs a function to return a set value.
   */
  public static function stub($function_name, $return_value) {
    self::$stubFunctionList[$function_name]['stub_value'] = $return_value;
  }

  public static function setDefaultStubValue($function_name) {
    self::$stubFunctionList[$function_name]['stub_value'] = $stubFunctionList[$function_name]['default_return_value'];
  }

  /**
   * Resets all the stubbed functions.
   */
  public static function resetStubs() {
    foreach (self::$stubFunctionList as &$stubFunctionListItem) {
      unset($stubFunctionListItem['stub_value']);
    }
  }

  /**
   * Returns all functions that would require stubs (based on a list of source files to search holistically).
   */
  public static function findFunctionsNeedingStubs($src_files) {
    // Loop through all the source files to find the ones that need to be searched.
    $completeFile = self::loadFiles($src_files);

    // Use a text-based regex search first to get an initial set. It won't be perfectly accurate (it includes function
    // calls that may be in comments, for example).
    preg_match_all(FUNCTION_REGEX, $completeFile, $matches);

    $function_call_like_strings = array_unique($matches[1]);

    // Do a token based search next based on the loaded files, which is closer but picks up some 
    // strings that don't entirely match.
    $tokens = token_get_all($completeFile);
    
    $string_tokens = array_unique(
        array_map(
            function($t) { return $t[1]; }, 
            array_filter($tokens, function($t) { return $t[0] == T_STRING; })
        )
      );

    // Then, do an intersection of both results to get the best set of functions (minus commented ones)
    // and unnecessary string elements.
    $function_list = array_intersect(array_unique($string_tokens), $function_call_like_strings);

    // Look for functions that do not exist.
    $result = array_filter($function_list, function($function) { return !function_exists(trim($function)); });  

    return $result;
  }

  public static function loadFiles($src_files) {
    $result = '';
    foreach ($src_files as $src_file) {
      include_once $src_file;
      $result .= file_get_contents($src_file);
    }

    return $result;
  }

  /**
   * Generate function stubs based on an array of function names.
   */
  public static function generateStubs($result) {
    $functionString = '';
    foreach ($result as $item) {
      $functionString .= 'function ' . $item . '() { return FunctionMock::getStubbedValue(__FUNCTION__); } ';
    }

    // Evaluate the actual function, therefore defining it.
    eval($functionString);

    return $functionString;
  }

  /**
   * Generate and execute stubbable functions based on a list of files.
   */
  public static function generateStubbableFunctions($src_files) {
    $function_list = self::findFunctionsNeedingStubs($src_files);

    return self::generateStubs($function_list);
  }
}

/**
 * Custom exception for a stub missing.
 */
class StubMissingException extends Exception
{
  public function __construct($functionName, $code = 0, Exception $previous = null) {
      $this->message = $functionName . ' has not been stubbed yet.' . "\r\n" .
        'Please call stub(\'' . $functionName . '\', <stub_value>) to set a value.';
 
      parent::__construct($this->message, $code, $previous);
  }

  // Return the string value.
  public function __toString() {
    return $this->message;
  }
}

?>