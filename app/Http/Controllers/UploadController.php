<?php namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use App\CssParser;

use Exception;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
 
class UploadController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request)
  {
    return view('upload', compact('error'));
  }

  public function upload(Request $request)
  {
    
    $file = $request->file('file');

    if (!$file->isValid())
    {
      $error = 'File is invalid';
      return view('error', compact('error'));
    }

    if (strtolower($file->extension()) != 'css')
    {
      $error = 'File is not CSS. Please ensure it has a .css extension';
      return view('error', compact('error'));
    }
    
    // Guess the mime type based on the file content rather than relying on the
    // client    
    if (!str_contains($file->getMimeType(), 'text'))
    {
      $error = 'File content does not appear to be text';
      return view('error', compact('error'));
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
           
      return view('stats', compact('stats'));
    }
    catch (Exception $e)
    {
      $error = $e->getMessage();
      return view('error', compact('error'));
    }
  }
}