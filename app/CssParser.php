<?php namespace App;

use Exception;

class CssParser
{

  //---------------------------
  //        Constants
  //---------------------------
  private static $MAX_LINES = 20000;
  private static $FONT_PROPERTY = 'font-family';
  private static $FONT_SIZE_PROPERTY = 'font-size';
  private static $COLOR_PROPERTY = 'color';
  private static $BG_COLOR_PROPERTY = 'background-color';
  private static $BORDER_TOP_COLOR    = 'border-top-color';
  private static $BORDER_LEFT_COLOR   = 'border-left-color';
  private static $BORDER_RIGHT_COLOR  = 'border-right-color';
  private static $BORDER_BOTTOM_COLOR = 'border-bottom-color';
  //private static $BACKGROUND_PROPERTY = 'background'; // TODO: support for border-color
  
  private static $IGNORE_COLORS       = ['transparent','initial','inherit'];
  private static $IGNORE_FONTS        = ['initial','inherit'];

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
  protected $media_query_count = 0;
  
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
  public function readFile($file_path)
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
    $this->media_query_count = 0;
    
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
   * Simply return the raw CSS blob (CSS without newlines)
   * @return string
   */
  public function getCssBlob()
  {
    return $this->cssBlob;
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
    $media_query_rulesets = 0;
    $important_count = 0;
    $ruleset_count = count($this->cssTree);
    $fonts_used = [];
    $colors_used = [];

    foreach ($this->cssTree as &$rulesets)
    {
      $selector_count += count($rulesets['selector']);

      foreach ($rulesets['declarations'] as &$declaration)
      {
        $value = $declaration['value'];

        if (!empty($declaration['important']))
        {
          $important_count++;
        }

        switch ($declaration['property'])
        {
          case static::$FONT_PROPERTY:

            if (!in_array($value, static::$IGNORE_FONTS))
            {
              if (is_array($value))
              {
                $fonts_used = array_merge($fonts_used, $value);
              }
              else
              {
                $fonts_used = array_merge($fonts_used, [$value]);
              }
            }

            break;

          case static::$COLOR_PROPERTY:
          case static::$BG_COLOR_PROPERTY:
          case static::$BORDER_TOP_COLOR:
          case static::$BORDER_LEFT_COLOR:
          case static::$BORDER_RIGHT_COLOR:
          case static::$BORDER_BOTTOM_COLOR:
            
            if (!in_array($value, static::$IGNORE_COLORS))
            {
              // Translate word-based color to hex
              $hex = static::_color_to_hex($value);
              
              if ($hex !== null)
              {
                $value = $hex;
              }
              else
              {
                $expanded_hex = static::_get_expanded_hex($value);
                
                if ($expanded_hex !== null)
                {
                  $value = $expanded_hex;
                }
              }

              $colors_used = array_merge($colors_used, [$value]);
            }
            
            break;
        }
      }
      
      if (!empty($rulesets['media_query']))
      {
        $media_query_rulesets++;
      }
    }
    
    return [
      'file_stats' => ['lines'      => $this->line_count,
                       'characters' => $this->character_count],
      'css_stats' => [
        'ruleset_count'        => $ruleset_count,
        'selector_count'       => $selector_count,
        'media_query_count'    => $this->media_query_count,
        'media_query_rulesets' => $media_query_rulesets,
        'important_count'      => $important_count,
        'fonts_used'           => array_unique($fonts_used),
        'colors_used'          => array_unique($colors_used),
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
        $this->media_query_count++;
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
    $declarationBlobLength = strlen($declarationBlob);

    // TODO: verify we actually have braces
    if ($declarationBlobLength <= 2)
    {
      // Declaration blob is empty (either "{}" or ""), ignore it...
      return;
    }

    // Remove the outer braces (simpler and quicker than regex)
    $declarationBlob = substr($declarationBlob, 1, $declarationBlobLength - 2);

    // Split things by semicolon...
    $propertySetBlobs = explode(';', $declarationBlob);

    $declarations = [];
    
    foreach ($propertySetBlobs as &$propertySetBlob)
    {
      $propertySetBlob = trim($propertySetBlob);
      
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
      
      if (static::_has_color_decimal_notation($set[1]))
      {
        $value = strtolower(trim($set[1]));
        $has_important_keyword = static::_remove_important($value);
      }
      else
      {

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

            $value_temp = trim($value_temp);
            
            if (!str_contains($value_temp, '"'))
            {
              $value_temp = strtolower($value_temp);
            }

            $has_important_keyword = static::_remove_important($value_temp);

            $value[] = $value_temp;
          }
        }
        else if ($values_temp_count == 1)
        {
          $value = strtolower(trim($values_temp[0]));
          $has_important_keyword = static::_remove_important($value);
        }
      }

      $declarations[] = [
        'property'  => strtolower(trim($set[0])),
        'value'     => $value,
        'important' => $has_important_keyword
      ];
    }
    
    return $declarations;
  }
  
  /**
   * Strip the '!important' keyword off of the attribute and return the status
   * of its existance
   * 
   * @param string $value reference to the value
   * @return boolean  true if the '!important' keyword exists, false otherwise
   */
  protected static function _remove_important(&$value)
  {
    $r = strpos($value, '!important');
    
    if ($r !== FALSE)
    {
      $value = trim(substr($value, 0, strlen($value)-10));
      return true;
    }
    
    return false;
  }
  
  /**
   * http://reference.sitepoint.com/css/colorvalues
   * @param type $value
   */
  protected static function _has_color_decimal_notation($value)
  {
    return str_contains($value, ['rgb(', 'rgba(', 'hsl(', 'hsla(']);
  }
  
  /**
   * http://reference.sitepoint.com/css/colorvalues#colorvalues__tbl_colourvalues_color-keywords
   * Not a comprehensive list, see:http://www.w3schools.com/colors/colors_names.asp
   * 
   * @param string $value the name of the color
   * @return string the hexadecimal result
   */
  protected static function _color_to_hex($value)
  {
    switch ($value)
    {
      case 'aqua':    return '#00ffff';
      case 'black':   return '#000000';
      case 'blue':    return '#0000ff';
      case 'fuchsia': return '#ff00ff';
      case 'gray':    return '#808080';
      case 'green':   return '#008000';
      case 'lime':    return '#00ff00';
      case 'maroon':  return '#800000';
      case 'navy':    return '#000080';
      case 'olive':   return '#808000';
      case 'orange':  return '#ffa500';
      case 'purple':  return '#800080';
      case 'red':     return '#ff0000';
      case 'silver':  return '#c0c0c0';
      case 'teal':    return '#008080';
      case 'white':   return '#ffffff';
      case 'yellow':  return '#ffff00';
      default: return null;
    }
  }
  
  /**
   * Given hex shorthand (#abc) expand it to the full value (#aabbcc)
   * @param string $value
   * @return string|null
   */
  protected static function _get_expanded_hex($value)
  {
    $len = strlen($value);

    if (($len >= 4) && ($value[0] == '#') && ($len < 7))
    {
      return '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
    }
    
    return null;
  }

}
