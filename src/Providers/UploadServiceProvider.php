<?php
namespace Codegor\Upload\Providers;

use Illuminate\Support\ServiceProvider;
use Codegor\Upload\Store;

class UploadServiceProvider extends ServiceProvider {
	/**
	 * {@inheritdoc}
	 */
	public function boot() {
		$path = realpath(__DIR__ . '/../../config/config.php');
		$this->publishes([$path => config_path('upload.php')], 'config');
		$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
		
	}
	
//	public function register() {
//		$this->app->bind('codegor.file', function () {
//			return new Store();
//		});
//	}
}