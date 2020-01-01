<?php

/** File */
Route::group(array_merge(['namespace' => 'Codegor\Upload\Http\Controllers'], config('route_group.route_group', [])), function () {
	Route::get('/file/{hash}', 'File');
});
