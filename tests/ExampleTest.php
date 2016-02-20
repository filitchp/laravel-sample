<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\CssParser;

class ExampleTest extends TestCase
{

  /**
   * A basic functional test example.
   *
   * @return void
   */
  public function testCssParser()
  {
    $css = '/*Some comment*/' . 
           '#foo, h1 a {FONT-FAMILY: lucida-sans, sans-serif; color: #ff0000; FONT-SIZE: 10pt; FONT-STYLE: normal; FONT-VARIANT: normal}' .
           '@media screen and (min-width: 480px) { /*Some other comment*/ body {background-color: lightgreen;}}' . 
           '/* another pesky comment */';

    $c = new CssParser($css);
    
    $cssTree = $c->parseCss();
    
    //print_r($cssTree);
    
    $this->assertEquals(2, count($cssTree));
    
    $first_ruleset = $cssTree[0];
    $selector_1 = $first_ruleset['selector'][0];
    $selector_2 = $first_ruleset['selector'][1];
    
    $this->assertEquals('#foo', $selector_1);
    $this->assertEquals('h1 a', $selector_2);
    
    $property_1 = $first_ruleset['declarations'][0]['property'];
    $value_1    = $first_ruleset['declarations'][0]['value'];
    
    $this->assertEquals('font-family', $property_1);
    $this->assertEquals(['lucida-sans', 'sans-serif'], $value_1);

    $property_2 = $first_ruleset['declarations'][1]['property'];
    $value_2    = $first_ruleset['declarations'][1]['value'];
    
    $this->assertEquals('color', $property_2);
    $this->assertEquals('#ff0000', $value_2);
    
    $stats = $c->getStats();
    
    $this->assertEquals(266, $stats['file_stats']['characters']);
    $this->assertEquals(2, $stats['css_stats']['ruleset_count']);
    $this->assertEquals(3, $stats['css_stats']['selector_count']);
    $this->assertEquals(['lucida-sans', 'sans-serif'], $stats['css_stats']['fonts_used']);
    $this->assertEquals(['#ff0000'], $stats['css_stats']['colors_used']);

    
  }
  
  
  public function testCssParserMissingBraces()
  {

    try
    {
      $c = new CssParser('p {color: #ff0000;');
      $c->parseCss();
      $this->assertTrue(false);
    }
    catch (Exception $e)
    {
      $this->assertTrue(true);
    }
    
    try
    {
      $c = new CssParser('p color: #ff0000;}');
      $c->parseCss();
      $this->assertTrue(false);
    }
    catch (Exception $e)
    {
      $this->assertTrue(true);
    }
    
    try
    {
      // Missing last brace
      $css = '@media screen and (min-width: 480px) {' .
             '  body {' .
             '    background-color: lightgreen;' .
             '  }';
      
      $c = new CssParser($css);
      $c->parseCss();
      $this->assertTrue(false);
    }
    catch (Exception $e)
    {
      $this->assertTrue(true);
    }
    
    try
    {
      // Missing inner brace
      $css = '@media screen and (min-width: 480px) {' .
             '  body ' .
             '    background-color: lightgreen;' .
             '  }'.
             '}';

      $c = new CssParser($css);
      $c->parseCss();
      $this->assertTrue(false);
    }
    catch (Exception $e)
    {
      $this->assertTrue(true);
    }
    
  }

}
