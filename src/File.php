<?php
namespace Codegor\Upload;

use Auth;

class File {
  private static $_share_pref = 'share://';
  private static $_share_pref_pattern = 'share\:\/\/';
  
  private static function _getFileMatchPattern(): string {
    $user_id =  (Auth::check()) ? Auth::user()->id : -1;
    return '/^files\/' . $user_id . '\/.{32,37}\..{3,4}$/';
  }
  private static function _getZipFileMatchPattern(): string {
    $user_id =  (Auth::check()) ? Auth::user()->id : -1;
    return '/^files\/zips\/' . $user_id . '\/.{32,37}\.zip$/';
  }
  private static function _getSharedFileMatchPattern(): string {
    return '/^'.self::$_share_pref_pattern.'files\/\d{1,12}\/.{32,37}\..{3,4}$/';
  }
  private static function _encrypt(string $d): string {
   return Encryptor::encrypt($d);
  }
  private static function _decrypt(string $h): string {
	  return Encryptor::decrypt($h);
  }
  
  public static function checkFileUrl(string $url): bool {
    try {
      $path = self::_decrypt($url);
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
      return false;
    }
    return preg_match(self::_getFileMatchPattern(), $path) > 0;
  }
  
  public static function getFilePath(string $url):?string { //:string|bool
    try {
      $path = self::_decrypt($url);
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
      return null;
    }
    $res = preg_match(self::_getFileMatchPattern(), $path) > 0;
    
    if (!$res)
      $res = preg_match(self::_getZipFileMatchPattern(), $path) > 0;
      
    if (!$res) {
      $res = preg_match(self::_getSharedFileMatchPattern(), $path) > 0;
      $path = ($res) ? substr($path, 8) : $path;
    }
    return ($res) ? $path : null;
  }
  
  public static function getFileUrl(string $path): string {
    return self::_encrypt($path);
  }
	
	public static function getSharedFileUrl(string $path): string {
		return self::_encrypt(self::$_share_pref.$path);
	}
  
  public static function shareFile(string $url): string {
    try {
      $path = self::_decrypt($url);
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
      return false;
    }
    $res = preg_match(self::_getFileMatchPattern(), $path) > 0;
    if(!$res) return false;
    
    return self::getFileUrl(self::$_share_pref.$path);
  }
}