<?php
namespace Codegor\Upload\Http\Controllers;

use Illuminate\Routing\Controller;
use Codegor\Upload\File as Lib;

class File extends Controller {
  public function __invoke($hash) {
    $path = Lib::getFilePath($hash);
    if(false === $path)
      return response('Not found', 404);
  
    return response()->file(storage_path('app/'.$path));
  }
}
