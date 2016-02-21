<?php namespace App;

use Exception;

class CssParser
{

  //---------------------------
  //        Constants
  //---------------------------
  private static $MAX_LINES = 10000;
  private static $FONT_PROPERTY = 'font-family';
  private static $FONT_SIZE_PROPERTY = 'font-size';
  private static $COLOR_PROPERTY = 'color';
  private static $BG_COLOR_PROPERTY = 'background-color';

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
   * @param string $filepath  The CSS file on disk to read
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
   * @return array  the CSS 'tree' structure
   * @throws Exception  if no CSS data was provided
   */
  public function parseCss()
  {
    if (empty($this->cssBlob))
    {
      throw new Exception('No CSS data provided');
    }
    
    $this->_preprocessCssBlob();
    
    $this->_extractCssRulesetsFromBlob($this->cssBlob, $this->cssRulesets);
    
    $this->_parseCssRulesets($this->cssRulesets);
    
    return $this->cssTree;

  }
  
  /**
   * Get stats about the CSS that was parsed
   * 
   * @throws Exception  if CSS data has not been parsed
   */
  public function getStats()
  {
    if (empty($this->cssTree))
    {
      throw new Exception('There is no parsed CSS data');
    }
    
    $selector_count    = 0;
    $media_query_count = 0;
    $ruleset_count = count($this->cssTree);
    $fonts_used = [];
    $colors_used = [];

    foreach ($this->cssTree as &$rulesets)
    {
      $selector_count += count($rulesets['selector']);

      foreach ($rulesets['declarations'] as &$declaration)
      {
        $value = $declaration['value'];
        
        //print_r($declaration['property']);

        switch ($declaration['property'])
        {
          case static::$FONT_PROPERTY:

            if (is_array($value))
            {
              $fonts_used = array_merge($fonts_used, $value);
            }
            else
            {
              $fonts_used = array_merge($fonts_used, [$value]);
            }

            break;
            
          case static::$FONT_SIZE_PROPERTY:
            
            break;
          
          case static::$COLOR_PROPERTY:
          case static::$BG_COLOR_PROPERTY:
            $colors_used = array_merge($colors_used, [$value]);
            break;
        }
      }
      
      if (!empty($rulesets['media_query']))
      {
        $media_query_count++;
      }
    }

    return [
      'file_stats' => ['lines'      => $this->line_count,
                       'characters' => $this->character_count],
      'css_stats' => [
        'ruleset_count'     => $ruleset_count,
        'selector_count'    => $selector_count,
        'media_query_count' => $media_query_count,
        'fonts_used'        => array_unique($fonts_used),
        'colors_used'       => array_unique($colors_used),
      ]
    ];
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
   * Extract valid CSS rulesets from the CSS blob
   * 
   * @throws Exception  if the syntax is invalid
   */
  protected function _extractCssRulesetsFromBlob(&$cssBlob, &$cssRuleset)
  {
    $start      = 0;
    $braceDepth = 0;
    $haveBrace  = false;
    
    // Compute a new string length since the blob might have been shortened during
    // preprocessing
    $blobLength = strlen($cssBlob);
    
    for ($i = 0; $i < $blobLength; $i++)
    {

      // Find a set of properties to isolate a CSS rulset block
      // Be sure to account for nested braces (commonly found with @media rules)
      if ($cssBlob[$i] == '{')
      {
        $braceDepth++;
        $haveBrace = true;
      }
      else if ($cssBlob[$i] == '}')
      {
        $braceDepth--;
      }

      if ($haveBrace && ($braceDepth == 0))
      {
        // We found a ruleset, now store it...
        $rulesetLength = $i - $start + 1;

        $cssRuleset[] = trim(substr($cssBlob, $start, $rulesetLength));
        
        $start = $i + 1;
        $haveBrace  = false;
      }
      
      if (($i == ($blobLength - 1)) && ($braceDepth != 0))
      {
        // Forgot to open/close a brace
        throw new Exception('Invalid CSS syntax: missing brace!');
      }
    }
  }
  
  /**
   * Third phase: For each CSS ruleset parse selectors and declarations
   * 
   * @throws Exception  if the selector/declaration is not found
   */
  protected function _parseCssRulesets(&$cssRulesets, $mediaQuery = '')
  {
    foreach ($cssRulesets as &$cssRuleset)
    {
      $selectorMatches = [];

      // Get everthing up until the first '{'
      preg_match("/[^{]*/", $cssRuleset, $selectorMatches);

      if (empty($selectorMatches[0]))
      {
        throw new Exception('Selector/rule is missing!');
      }
      
      $selectorBlob = trim($selectorMatches[0]);
      
      $selectorBlobLength = strlen($selectorBlob);

      // The declaration part is everything else
      $remainingBlob = trim(substr($cssRuleset, $selectorBlobLength));
      
      if (str_contains($selectorBlob, '@media'))
      {
        
        $mediaRulesets = [];
        $declarationsMatches = [];

        $remainingBlobLength = strlen($remainingBlob);

        if ($remainingBlobLength <= 2)
        {
          // Remaining blob is empty (either "{}" or ""), ignore it...
          continue;
        }

        // Remove the outer braces (simpler and quicker than regex)
        $mediaRulesetBlob = substr($remainingBlob, 1, $remainingBlobLength - 2);

        //print_r($mediaRulesetBlob);

        $this->_extractCssRulesetsFromBlob($mediaRulesetBlob, $mediaRulesets);

        //print_r($mediaRulesets);
        
        $mediaQuery = trim($selectorBlob);
        
        // Recursive call to parse media query rulesets
        $this->_parseCssRulesets($mediaRulesets, $mediaQuery);
      }
      else
      {
        $selectors = $this->_parseCssSelectorBlob($selectorBlob);

        $declarations = $this->_parseCssDeclarationBlob($remainingBlob);

        $this->cssTree[] = ['selector'     => $selectors,
                            'declarations' => $declarations,
                            'media_query'  => $mediaQuery];
      }
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
   * @param string $declarationBlob
   * @throws Exception if no properties are found
   */
  protected function _parseCssDeclarationBlob(&$declarationBlob)
  {
    // Get everything inside of the braces { }
    $declarationsMatches = [];
    preg_match("/\{(.*?)\}/", $declarationBlob, $declarationsMatches);

    if (empty($declarationsMatches[1]))
    {
      throw new Exception('Declaration is empty!');
    }

    // Split things by semicolon...
    $propertySetBlobs = explode(';', $declarationsMatches[1]);

    $declarations = [];
    
    foreach ($propertySetBlobs as &$propertySetBlob)
    {
      if (empty($propertySetBlob))
      {
        continue;
      }
      
      $set = explode(':', $propertySetBlob);
      
      if (!isset($set[0]))
      {
        throw new Exception('Invalid declaration, missing property!');
      }
      
      if (!isset($set[1]))
      {
        throw new Exception('Invalid declaration, missing value!');
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

      $declarations[] = [
        'property' => strtolower(trim($set[0])),
        'value'    => $value
      ];
    }
    
    return $declarations;
  }

}
