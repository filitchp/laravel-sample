<?php namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use App\CssParser;

use Redirect;
use Session;
use Storage;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
 
class UploadController extends Controller {

  private static $ERROR_FILE_NOT_PROVIDED = '1';
  private static $ERROR_FILE_INVALID      = '2';
  private static $ERROR_FILE_NOT_CSS      = '3';
  private static $ERROR_FILE_SUSPICIOUS   = '4';

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request)
  {
    // Simple error display without relying on session
    switch ($request->input('error', '-1'))
    {
      case static::$ERROR_FILE_NOT_PROVIDED:
        $error = 'No file provided or file too large';
        break;
      
      case static::$ERROR_FILE_INVALID:
        $error = 'File is invalid';
        break;
      
      case static::$ERROR_FILE_NOT_CSS:
        $error = 'File is not CSS. Please ensure it has a .css extension';
        break;
      
      case static::$ERROR_FILE_SUSPICIOUS:
        $error = 'File content does not appear to be text';
        break;
    }

    return view('upload', compact('error'));
  }

  public function upload(Request $request)
  {
    
    if (!$request->hasFile('cssfile'))
    {
      return Redirect::to('/?error=' . static::$ERROR_FILE_NOT_PROVIDED);
    }
    
    $file = $request->file('cssfile');

    if (!$file->isValid())
    {
      return Redirect::to('/?error=' . static::$ERROR_FILE_INVALID);
    }

    if (strtolower($file->extension()) != 'css')
    {
      return Redirect::to('/?error=' . static::$ERROR_FILE_NOT_CSS);
    }
    
    // Guess the mime type based on the file content rather than relying on the
    // client    
    if (!str_contains($file->getMimeType(), 'text'))
    {
      return Redirect::to('/?error=' . static::$ERROR_FILE_SUSPICIOUS);
    }
        
    try
    {
      $file_path = $file->getPathname();

      if (!file_exists($file_path))
      {
        throw new Exception('File not found.');
      }

      $c = new CssParser();
      
      $c->readFile($file_path);
      $c->parseCss();
      $stats = $c->getStats();
      
      $stats_json = json_encode($stats);
      
      Storage::put('blob.css', $c->getCssBlob());
      
      Storage::put('stats.json', $stats_json);
      
      return view('stats', compact('error', 'stats'));
    }
    catch (Exception $e)
    {
      // send error message if you can
    }
  }
}