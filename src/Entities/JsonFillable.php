<?php

namespace Codegor\Upload\Entities;


use Codegor\Upload\Store;
use Illuminate\Support\Arr;

trait JsonFillable {

//  protected $nameFillable = [ // method nameFill()
//    "key" => "string", // string, array, int,
//  ];
//	protected array $nameToggleable = [
//		'key' => 'array'  // toggle__KEY__: value
//	];
//	protected array $nameAddable = [
//		'key' => 'array' // add__KEY__: value
//	];
//	protected array $nameDeletable = [
//		'key' => 'array' // delete__KEY__: value
//	];
//	protected $nameFileFillable = [ // method nameFileFill()
//		'key' => [
//			'from' => 'path.path',
//			'to' => 'path',
//      //'access' => 'public' // || 'private', def - public or without this field = public
//		],
//		'key2' => [
//			'type' => 'collection',
//			'from' => 'path.inside.item',
//			'to' => 'path.in.item'
//      'access' => 'private' // || 'private', def - public
//		]
//	];
  
  public function __call($method, $parameters) {
    if(strpos($method, 'FileFill')){
      $param = explode('FileFill', $method)[0];
      if(property_exists($this, $param.'FileFillable'))
        return $this->jsonFillFileJob($param, $this->{$param.'FileFillable'}, $parameters[0]);
    
      else
        trigger_error("Entity ".self::class." doesn't have ".$param."FileFillable param but jsonFillFile method called...", E_WARNING);
    
      return $this;
    }
    
    if(strpos($method, 'Fill')){
      $param = explode('Fill', $method)[0];
      if(property_exists($this, $param.'Fillable'))
	      $this->jsonFillJob($param, $this->{$param . 'Fillable'}, $parameters[0]);
      if(property_exists($this, $param.'Toggleable'))
	      $this->jsonToggleJob($param, $this->{$param . 'Toggleable'}, $parameters[0]);
      if(property_exists($this, $param.'Addable'))
	      $this->jsonAddJob($param, $this->{$param . 'Addable'}, $parameters[0]);
      if(property_exists($this, $param.'Deletable'))
	      $this->jsonDelJob($param, $this->{$param . 'Deletable'}, $parameters[0]);
				
      if(!(property_exists($this, $param.'Fillable') || property_exists($this, $param.'Toggleable')
	      || property_exists($this, $param.'Addable')|| property_exists($this, $param.'Deletable')))
        trigger_error("Entity ".self::class." doesn't have ".$param."Fillable param but jsonFill method called...", E_WARNING);
      
      return $this;
    }
    
    return parent::__call($method, $parameters);
  }
	
	private function _each(string $field, array $fills, array $data, callable $action, bool $checkType = true) {
		$res = $this->{$field} ? $this->{$field} : [];
		foreach ($fills as $key => $val) {
			if (isset($data[$key]) && (!$checkType || ($checkType && is_callable('is_' . $val) && call_user_func('is_' . $val, $data[$key])))) {
				$old = $res[$key] ?? null;
				$res[$key] = $action($data[$key], $key, $res, $val);
				if (property_exists($this, $field . 'Logged') && is_array($this->{$field . 'Logged'})
					&& isset($this->{$field . 'Logged'}[$key]) && method_exists($this, $this->{$field . 'Logged'}[$key]))
					$this->{$this->{$field . 'Logged'}[$key]}($data[$key], $old);
			}
		}
		
		$this->{$field} = $res;
		return $this;
  }
	
	public function jsonFillJob(string $field, array $fills, array $data): self {
		return $this->_each($field, $fills, $data, function($val, $key, &$res, $type){
			return $val;
		});
	}
	
	public function jsonToggleJob(string $field, array $fills, array $data): self {
		return $this->_each($field, $fills, $data, function($val, $key, &$res, $type){
			if('array' == $type){
				$r = $res[$key] ?? [];
				if(!in_array($val, $r))
					$r[] = $val;
				else {
					array_splice($r, array_search($val, $r), 1);
				}
			} else if('string' == $type){
				$r = $res[$key] ?? null;
				$r = ($val == $r) ? null : $val;
			} else if('numeric' == $type){
				$r = $res[$key] ?? null;
				$r = ($val == $r) ? null : $val;
			} else //if('bool' == $type)
				$r = isset($res[$key]) ? !$res[$key] : true;
			
			return $r;
		}, false);
	}
	
	public function jsonAddJob(string $field, array $fills, array $data): self {
		return $this->_each($field, $fills, $data, function($val, $key, &$res, $type){
			if('array' == $type){
				$r = $res[$key] ?? [];
				if(!in_array($val, $r))
					$r[] = $val;
			} else if('string' == $type){
				$r = $res[$key] ?? '';
				$r .= (string) $val;
			} else if('numeric' == $type){
				$r = $res[$key] ?? 0;
				$r += (double) $val;
			} else //if('bool' == $type)
				$r = true;
			
			return $r;
		}, false);
	}
	
	public function jsonDelJob(string $field, array $fills, array $data): self {
		return $this->_each($field, $fills, $data, function($val, $key, &$res, $type) use ($field){
			if('array' == $type){
				$r = $res[$key] ?? [];
				if(in_array($val, $r))
					array_splice($r, array_search($val, $r), 1);
			} else if('string' == $type){
				$r = $res[$key] ?? '';
				$r = str_replace($val, '', $r);
			} else //if('bool' == $type) if('numeric' == $type)
				$r = null;
			
			return $r;
		}, false);
	}
  
  public function jsonFillFileJob(string $field, array $fills, array $props): self {
	  $res = $this->{$field} ? $this->{$field} : [];
	  $hasFile = false;
	  foreach ($fills as $f => $cnf) { // if src omit or empty and file empty del from DB info
		  if(!isset($props[$f]))
			  continue; // props does not present
		
		  $file = $props[$f];
		
		  $delFile = false;
		  if(isset($cnf['type']) && 'collection' == $cnf['type']){
			  if(!$this->_fileCollectionProcess($file, $cnf)){ // if collection empty
				  if(isset($res[$f]))
					  $delFile = true;
				  else
					  continue; // props present but empty and nothing to delete
			  }
		  } else if(!$this->_fileProcess($file, $cnf)){ // if file doesn't present
			  if(Arr::has($file, $cnf['to']) && ('' == Arr::get($file, $cnf['to']) || !$this->_checkSrc($file, $cnf)) && isset($res[$f]))
				  $delFile = true;
			  else
				  continue; // props present but nothing store or delete
		  }
		
		  $hasFile = true;
		
		  if($delFile)
			  unset($res[$f]);
		  else
			  $res[$f] = $file;
	  }
	
	  if($hasFile)
		  $this->{$field} = $res;
	
	  return $this;
  }
  
  private function _fileProcess(array &$file, array $cnf): bool {
	  if(!(Arr::has($file, $cnf['from']) && Arr::has($file, $cnf['from'].'.data'))){
		  if(Arr::has($file, $cnf['from']))
			  Arr::forget($file, $cnf['from']);
		
		  return false;
	  }
	
	  $url = Store::uploadFileRndName(Arr::get($file, $cnf['from'].'.type'), Arr::get($file, $cnf['from'].'.data'),
		  isset($cnf['access']) && 'private' == $cnf['access']);
	  Arr::set($file, $cnf['to'], $url);
	  Arr::forget($file, $cnf['from']);
	
	  return true;
  }
  
  private function _fileCollectionProcess(array &$file, array $cnf): bool {
	  if(0 == count($file))
		  return false;
	
	  foreach ($file as $k => &$item) {
		  if (!$this->_fileProcess($item, $cnf)){  // if file doesn't present and src not our dell src
			  if(Arr::has($item, $cnf['to']) && ('' == Arr::get($item, $cnf['to']) || !$this->_checkSrc($item, $cnf)))
				  unset($file[$k]);
		  }
	  }
	  unset($item);
	
	  if(0 == count($file))
		  return false;
	
	  return true;
  }
	
	private function _checkSrc(array &$file, array $cnf): bool   {
		$src = Arr::get($file, $cnf['to']);
		return !('' != $src && false === Store::checkUrl($src));
	}
}
