<?php
namespace Codegor\Upload\Providers;

use Illuminate\Support\ServiceProvider;
use Codegor\Upload\Store;
use Validator;

class UploadServiceProvider extends ServiceProvider {
	/**
	 * {@inheritdoc}
	 */
	public function boot() {
		$path = realpath(__DIR__ . '/../../config/config.php');
		$this->publishes([$path => config_path('upload.php')], 'config');
		$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
		
		
		Validator::extend('inkey', function ($attribute, $value, $parameters, $validator) {
			if (is_array($value) && $validator->hasRule($attribute, 'Array'))
				return count(array_diff(array_keys($value), $parameters)) === 0;
			else
				return false;
		});
		
		Validator::extend('indexed', function ($attribute, $value, $parameters, $validator) {
			/*
			$is_assoc = function ($array) {
				$keys = array_keys($array);
				return $keys !== array_keys($keys);
			};
			$array_type = function ($obj) {
				$last_key = -1;
				$type = 'index';
				foreach ($obj as $key => $val) {
					if (!is_int($key) || $key < 0) {
						return 'assoc';
					}
					if ($key !== $last_key + 1) {
						$type = 'sparse';
					}
					$last_key = $key;
				}
				return $type;
			};
			*/
			
			if (is_array($value) && $validator->hasRule($attribute, 'Array')) {
				$keys = array_keys($value);
				return $keys === array_keys($keys);
			} else
				return false;
		});
		
		Validator::extend('starts_with_empty', function ($attribute, $value, $parameters, $validator) {
			return (is_string($value) && trim($value) === '') || $validator->validateStartsWith($attribute, $value, $parameters);
		});
	}
	
//	public function register() {
//		$this->app->bind('codegor.file', function () {
//			return new Store();
//		});
//	}
}