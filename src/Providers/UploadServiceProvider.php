<?php
/*
 * This file is part of acl.
 *
 * (c) Egor <codeegor@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Codegor\Acl\Providers;

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