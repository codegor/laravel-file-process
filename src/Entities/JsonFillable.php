<?php

namespace Codegor\Upload\Entities;


use Codegor\Upload\Store;
use Illuminate\Support\Arr;

trait JsonFillable {

//  protected $nameFillable = [ // method nameFill()
//    "key" => "string", // string, array, int,
//  ];
//	protected $nameFileFillable = [ // method nameFileFill()
//		'key' => [
//			'from' => 'path.path',
//			'to' => 'path'
//		],
//		'key2' => [
//			'type' => 'collection',
//			'from' => 'path.inside.item',
//			'to' => 'path.in.item'
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
        return $this->jsonFillJob($param, $this->{$param.'Fillable'}, $parameters[0]);
      
      else
        trigger_error("Entity ".self::class." doesn't have ".$param."Fillable param but jsonFill method called...", E_WARNING);
      
      return $this;
    }
    
    return parent::__call($method, $parameters);
  }
	
	public function jsonFillJob(string $field, array $fills, array $data): self {
		$res = $this->{$field} ? $this->{$field} : [];
		foreach ($fills as $key => $val) {
			if (isset($data[$key]) && is_callable('is_' . $val) && call_user_func('is_' . $val, $data[$key])) {
				$old = $res[$key];
				$res[$key] = $data[$key];
				if(property_exists($this, $field.'Logged') && is_array($this->{$field.'Logged'})
					&& isset($this->{$field.'Logged'}[$key]) && method_exists($this, $this->{$field.'Logged'}[$key]))
					$this->{$this->{$field.'Logged'}[$key]}($data[$key], $old);
			}
		}
		
		$this->{$field} = $res;
		return $this;
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
	
	  $url = Store::uploadFileRndName(Arr::get($file, $cnf['from'].'.type'), Arr::get($file, $cnf['from'].'.data'));
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
