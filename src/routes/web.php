<?php

/** File */
Route::group(array_merge(['namespace' => 'Codegor\Upload\Http\Controllers', 'middleware' =>['web']], config('upload.route_group', [])), function () {
	Route::get('/file/{hash}', 'File');
});
