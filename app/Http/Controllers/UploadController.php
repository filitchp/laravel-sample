<?php namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;

use Redirect;
use Session;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
 
class UploadController extends Controller {
  
  
  private static $ERROR_FILE_NOT_PROVIDED = '1';
  private static $ERROR_FILE_INVALID      = '2';
  private static $ERROR_FILE_NOT_CSS      = '3';
 
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
        $error = 'File is not css';
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
    
    try
    {
      $file_path = $file->getPathname();

      if (!file_exists($file_path))
      {
        throw new Exception('File not found.');
      }

      $file_handle = fopen($file_path, "rb");

      if (!$file_handle)
      {
        throw new Exception('File open failed.');
      }

      while (!feof($file_handle))
      {
        echo fgets($file_handle) . "<br>";
      }

      fclose($file_handle);

      // send success JSON
    }
    catch (Exception $e)
    {
      // send error message if you can
    }
  }
  
  
  private static function parse_css()
  {
    
  }
}