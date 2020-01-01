<?php
namespace Codegor\Upload\Http\Controllers;

use Illuminate\Http\Request;
use Codegor\Upload\File as Lib;

class File extends Controller {
  public function __invoke($hash) {
    $path = Lib::getFilePath($hash);
    if(false === $path)
      return response('Not found', 404);
  
    return response()->file(storage_path('app/'.$path));
  }
}
