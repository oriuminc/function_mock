<?php

define('FUNCTION_NAME_REGEX', '/(\w+)\s*\(.*\)/'); // any string that ends with a "("
define('FUNCTION_REGEX', '/\w+\((?<My_Group>(?:\([^()]*\)|.)*)\)/'); // any string that ends with a "("

define('TOKEN_CODE', 0);
define('TOKEN_VALUE', 1);
define('SPACE_CODE', 371);


/**
 * Class that supports stubbing functions.
 */
class FunctionMock
{
  private static $stubFunctionList = array();

  public static function getStubbedFunctionList() {
    return self::$stubFunctionList;
  }

  /**
   * Returns a stubbed value based on the function name.
   *
   * @param $function_name
   *   The name of the function to retrieve the stubbed value from.
   * @return
   *   The stubbed value for the function, if it exists. Otherwise it
   *   will throw an exception asking a stubbed value to be defined.
   * @throws StubMissingException
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
   *
   * @param $function_name
   *   The name of the function to retrieve the stubbed value from.
   * @return
   *   The stubbed value for the function, if it exists. Otherwise it
   *   will throw an exception asking a stubbed value to be defined.
   * @throws StubMissingException
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


  private static function findPrevNonSpaceToken(&$tokens, $i) {
    $counter = $i - 1;

    if ($counter <= 0) {
      return array(TOKEN_CODE => T_FUNCTION);
    }

    while ($tokens[$counter][TOKEN_CODE] === SPACE_CODE &&
            $counter >= 0) {
      $counter--;  
    }

    return $tokens[$counter];
  }

  private static function findNextNonSpaceToken(&$tokens, $i) {
    $counter = $i + 1;    

    if ($counter >= count($tokens)) {
      return array(TOKEN_CODE => T_FUNCTION);
    }

    while ($tokens[$counter][TOKEN_CODE] === SPACE_CODE &&
            $counter < count($tokens)) {
      $counter++;  
    }

    return $tokens[$counter];
  }

  /**
   * Returns all functions that would require stubs (based on a list of source files to search holistically).
   */
  public static function findFunctionsNeedingStubs($src_files) {
    // Loop through all the source files to find the ones that need to be searched.
    $completeFile = self::loadFiles($src_files);

    // Use a text-based regex search first to get an initial set. It won't be perfectly accurate (it includes function
    // calls that may be in comments, for example).
    // preg_match_all(FUNCTION_REGEX, $completeFile, $matches);

    // var_export($matches);

    // $names = preg_grep(FUNCTION_NAME_REGEX, $matches[0]);
    
    // var_export($names);

    // $matches2 = preg_grep(FUNCTION_REGEX, $matches[1]);

    // var_export($matches2);

    // $function_call_like_strings = array_unique($matches[1]);

    // Do a token based search next based on the loaded files, which is closer but picks up some 
    // strings that don't entirely match.
    $tokens = token_get_all($completeFile);

    // file_put_contents('block_tokens.txt', $tokens);

    $result = array();

    for ($i = 0; $i < count($tokens); $i++) {
      $token = $tokens[$i];

      // if ($i + 1 > count($tokens) - 1 || $i - 1 < 0) {
      //   continue;
      // }

      if ($token[TOKEN_CODE] !== T_STRING) {
        continue;
      }

      $next_token = FunctionMock::findNextNonSpaceToken($tokens, $i);

      // var_export("previous \r\n");
      // var_export($prev_token);
      // var_export("current \r\n");
      // var_export($token);
      // var_export("next \r\n");
      // var_export($next_token);

      if ($next_token[TOKEN_CODE] !== '(') {
          // Not a function call.
          continue;
      }

      $prev_token = FunctionMock::findPrevNonSpaceToken($tokens, $i);

      if ($prev_token[TOKEN_CODE] === T_FUNCTION) {
          // It's a function definition, not a function call.
          continue;
      }

      if ($prev_token[TOKEN_CODE] === T_OBJECT_OPERATOR) {
          // It's a method invocation, not a function call.
          continue;
      }

      if ($prev_token[TOKEN_CODE] === T_DOUBLE_COLON) {
          // It's a static method invocation, not a function call.
          continue;
      }

      if ($prev_token[TOKEN_CODE] === T_NEW) {
          // It's a class instantiation.
          continue;
      }

      // If it gets all the way here, it's a function call.
      $result[] = $token[TOKEN_VALUE];
    }

    // var_export($result);

    // $string_tokens = array_unique(
    //     array_map(
    //         function($t) { return $t[1]; }, 
    //         array_filter($tokens, function($t) { return $t[0] == T_STRING; })
    //     )
    //   );

    // var_export($string_tokens);
    

    // // Then, do an intersection of both results to get the best set of functions (minus commented ones)
    // // and unnecessary string elements.
    // $function_list = array_intersect(array_unique($string_tokens), $function_call_like_strings);

    // Look for functions that do not exist.
    $result = array_filter($result, function($function) { return !function_exists(trim($function)); });  

    return $result;
  }

  // public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
  //   {

  //       $tokens       = $phpcsFile->getTokens();
  //       $functionName = $tokens[$stackPtr]['content'];

  //       // Find the next non-empty token.
  //       $openBracket = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr + 1), null, true);

  //       if ($tokens[$openBracket]['code'] !== T_OPEN_PARENTHESIS) {
  //           // Not a function call.
  //           return;
  //       }

  //       if (isset($tokens[$openBracket]['parenthesis_closer']) === false) {
  //           // Not a function call.
  //           return;
  //       }

  //       // Find the previous non-empty token.
  //       $search   = PHP_CodeSniffer_Tokens::$emptyTokens;
  //       $search[] = T_BITWISE_AND;
  //       $previous = $phpcsFile->findPrevious($search, ($stackPtr - 1), null, true);
  //       if ($tokens[$previous]['code'] === T_FUNCTION) {
  //           // It's a function definition, not a function call.
  //           return;
  //       }

  //       if ($tokens[$previous]['code'] === T_OBJECT_OPERATOR) {
  //           // It's a method invocation, not a function call.
  //           return;
  //       }

  //       if ($tokens[$previous]['code'] === T_DOUBLE_COLON) {
  //           // It's a static method invocation, not a function call.
  //           return;
  //       }

  //       $this->phpcsFile    = $phpcsFile;
  //       $this->functionCall = $stackPtr;
  //       $this->openBracket  = $openBracket;
  //       $this->closeBracket = $tokens[$openBracket]['parenthesis_closer'];
  //       $this->arguments    = array();

  //       $phpcsFile->addWarning(
  //               'Function Call ' . $tokens[$stackPtr]['content'],
  //               $stackPtr
  //           );

  //   }//end process()  

  public static function loadFiles($src_files) {
    $result = '';
    foreach ($src_files as $src_file) {
      include_once $src_file;
      $result .= file_get_contents($src_file);
    }

    return $result;
  }

  // private static function generateRandomString($length = 5) {
  //     $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  //     $randomString = '';
  //     for ($i = 0; $i < $length; $i++) {
  //         $randomString .= $characters[rand(0, strlen($characters) - 1)];
  //     }
  //     return $randomString;
  // }

  /**
   * Generate actual function definitions that can be stubbed, based on an array of function names.
   */
  public static function createStubbableFunctionDefinitions($result) {
    $functionString = '';
    foreach ($result as $item) {
      // $tempFile = FunctionMock::generateRandomString().".php";

      // var_export($tempFile);
      
      $newFunctionDefinition = 'function ' . $item . '() { return FunctionMock::getStubbedValue(__FUNCTION__); } ';

      if (!function_exists($item)) {
        eval($newFunctionDefinition);
      }
      // Create a temporary file so you can run include_once on it. This is safer than eval(), since if it already exists
      // it throws a PHP fatal error that unrecoverable. There might be situations that a function definition is attempted
      // more than once, depending on the file being parsed.
      // file_put_contents($tempFile, $newFunctionDefinition);

      // Try including it once.
      // include $tempFile;

      // Delete the file now.
      //unlink(realpath($tempFile));

      $functionString .= $newFunctionDefinition;
    }

    // // Evaluate the actual function definitions, therefore making them real. The risk of doing this is that sometimes
    // // you will end up redefining a function if it already exists (if you are running this within the same
    // // thread), since you can't necessarily "undefine" it as a teardown event. This is the side effect of not generating a file
    // // and using include_once on it).
    // try {
    //   eval($functionString);
    // } catch (Exception $e) {
    //   // Ignore for now.
    // }

    return $functionString;
  }

  /**
   * Generate and execute stubbable functions based on a list of files.
   */
  public static function generateStubbableFunctions($src_files) {
    $function_list = self::findFunctionsNeedingStubs($src_files);

    return self::createStubbableFunctionDefinitions($function_list);
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