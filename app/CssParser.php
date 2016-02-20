<?php namespace App;

use Exception;

class CssParser
{

  //---------------------------
  //        Constants
  //---------------------------
  private static $MAX_LINES = 10000;
  
  //---------------------------
  //       Properties
  //---------------------------
  // Stores all input CSS as a string with no newlines
  protected $cssBlob = [];

  // Stores the raw (un-parsed) rulesets
  protected $cssRulesets = [];

  // Stores the parsed CSS in a heirarchy
  protected $cssTree = [];

  // Stats
  protected $line_count      = 0;
  protected $selector_count  = 0;
  protected $rule_count      = 0;
  protected $character_count = 0;
  
  //----------------------------------------------------------------------------
  //  CSS ruleset syntax
  //----------------------------------------------------------------------------
  //     
  //       Selector Grouping  Declaration
  //       _______|________    ____|____
  //      |                |  |         |
  //      h1, h2, h3, h4, h5 {color:blue; font-size:12px;}
  //      \                    \     \
  //       selector             \     value
  //                             property
  //                      
  //----------------------------------------------------------------------------
  
  /**
   * Constructor with optional CSS blob as input (must not contain new lines)
   * 
   * @param string $cssBlob a string containing the CSS to process
   */
  public function __construct($cssBlob = '')
  {
    
    $this->cssBlob = $cssBlob;
    
    $this->character_count = strlen($this->cssBlob);
    
    // TODO: confirm input string does not contain newlines...
    if ($this->character_count)
    {
      $this->line_count = 1;
    }
  }
  
  /**
   * Read a CSS file from disk
   * 
   * @param string $filepath
   */
  public function readFile($filepath)
  {
    $file_handle = fopen($file_path, "rb");

    if (!$file_handle)
    {
      throw new Exception('File open failed.');
    }
    
    $this->cssBlob = '';
    $this->cssTree = [];
    $this->line_count = 0;
    $this->character_count = 0;
    
    while (!feof($file_handle))
    {
      $line = fgets($file_handle);
      $this->cssBlob .= $line;
      
      $this->line_count++;
      
      $this->character_count += strlen($line);
      
      if ($this->line_count > static::$MAX_LINES)
      {
        throw new Exception('Max number of lines exceeded');
      }
    }

    fclose($file_handle);
  }

  /**
   * Parse the CSS and return the information in a hierarchical structure
   * 
   * @return array the CSS 'tree' structure
   * @throws Exception if no CSS data was provided
   */
  public function parseCss()
  {
    if (empty($this->cssBlob))
    {
      throw new Exception('No CSS data provided');
    }
    
    $this->_preprocessCssBlob();
    
    $this->_extractCssRulesetsFromBlob();
    
    $this->_parseCssRulesets();
    
    return $this->cssTree;

  }
  
  /**
   * First phase: clean up CSS to make sure we can process it
   */
  protected function _preprocessCssBlob()
  {
    // Remove all multiline comments (the only kind of comments supported by CSS)
    $this->cssBlob = preg_replace("/\/\*(.*?)\*\//", "", $this->cssBlob);
  }
  
  /**
   * Second phase: Extract valid CSS rulesets from the CSS blob
   * 
   * @throws Exception if the syntax is invalid
   */
  protected function _extractCssRulesetsFromBlob()
  {
    $start      = 0;
    $braceDepth = 0;
    $haveBrace  = false;
    
    // Compute a new string length since the blob might have been shortened during
    // preprocessing
    $blobLength = strlen($this->cssBlob);
    
    for ($i = 0; $i < $blobLength; $i++)
    {

      // Find a set of properties to isolate a CSS rulset block
      // Be sure to account for nested braces (commonly found with @media rules)
      if ($this->cssBlob[$i] == '{')
      {
        $braceDepth++;
        $haveBrace = true;
      }
      else if ($this->cssBlob[$i] == '}')
      {
        $braceDepth--;
      }

      if ($haveBrace && ($braceDepth == 0))
      {
        // We found a ruleset, now store it...
        $rulesetLength = $i - $start + 1;

        $this->cssRulesets[] = trim(substr($this->cssBlob, $start, $rulesetLength));
        
        $start = $i + 1;
        $haveBrace  = false;
      }
      
      if (($i == ($this->character_count - 1)) && ($braceDepth != 0))
      {
        // Forgot to open/close a brace
        throw new Exception('Invalid CSS syntax: missing brace!');
      }
    }
  }
  
  /**
   * Third phase: For each CSS ruleset parse selectors and declarations
   * 
   * @throws Exception if the selector/declaration is not found
   */
  protected function _parseCssRulesets()
  {
    foreach ($this->cssRulesets as &$cssRuleset)
    {
      $selectorMatches = [];

      // Get everthing up until the first '{'
      preg_match("/[^{]*/", $cssRuleset, $selectorMatches);

      if (empty($selectorMatches[0]))
      {
        throw new Exception('Selector/rule is missing!');
      }
      
      $selectorBlobLength = strlen($selectorMatches[0]);
      
      // Property/value pairs are everything else
      $declarationBlob = substr($cssRuleset, $selectorBlobLength);
      
      $selectorBlob = trim($selectorMatches[0]);

      $selectors = $this->_parseCssSelectorBlob($selectorBlob);

      $properties = $this->_parseCssDeclarationBlob($declarationBlob);
      
      $this->cssTree[] = ['selector'   => $selectors,
                          'properties' => $properties];
 
    }
    
    //print_r($this->cssTree);
  }
  
  /**
   * Given a selector string parse out the selectors/rules/queries
   * 
   * @param string $selectorBlob raw string representing the selector portion
   */
  protected function _parseCssSelectorBlob(&$selectorBlob)
  {
    // Split grouped selectors by commas...
    $selectorSets = explode(',', $selectorBlob);
    
    foreach ($selectorSets as &$selector)
    {
      $selector = trim($selector);
    }
    
    return $selectorSets;
  }
  
  /**
   * For a given string of properties get the corresponding property/value pairs
   * 
   * @param string $propertyBlob
   * @throws Exception if no properties are found
   */
  protected function _parseCssDeclarationBlob(&$propertyBlob)
  {
    // Get everything inside of the braces { }
    $propertyMatches = [];
    preg_match("/\{(.*?)\}/", $propertyBlob, $propertyMatches);

    if (empty($propertyMatches[1]))
    {
      throw new Exception('Properties not found!');
    }

    // Split things by semicolon...
    $propertySetBlobs = explode(';', $propertyMatches[1]);

    $propertySet = [];
    
    foreach ($propertySetBlobs as &$propertySetBlob)
    {
      if (empty($propertySetBlob))
      {
        continue;
      }
      
      $set = explode(':', $propertySetBlob);
      
      if (!isset($set[0]) || !isset($set[1]))
      {
        throw new Exception('Invalid property/value!');
      }

      $values_temp = explode(',', $set[1]);
      $values_temp_count = count($values_temp);
      
      if ($values_temp_count > 1)
      {
        $value = [];
        
        foreach ($values_temp as &$value_temp)
        {
          if (empty($value_temp))
          {
            continue;
          }
          
          $value[] = strtolower(trim($value_temp));
        }
      }
      else if ($values_temp_count == 1)
      {
        $value = strtolower(trim($values_temp[0]));
      }

      $propertySet[] = [
        'property' => strtolower(trim($set[0])),
        'value'    => $value
      ];
    }
    
    return $propertySet;
  }

}
