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
    $compress = function($input, $ascii_offset = 38){
      $input = strtoupper($input);
      $output = '';
      //We can try for a 4:3 (8:6) compression (roughly), 24 bits for 4 chars
      foreach(str_split($input, 4) as $chunk) {
        $chunk = str_pad($chunk, 4, '=');
      
        $int_24 = 0;
        for($i=0; $i<4; $i++){
          //Shift the output to the left 6 bits
          $int_24 <<= 6;
        
          //Add the next 6 bits
          //Discard the leading ascii chars, i.e make
          $int_24 |= (ord($chunk[$i]) - $ascii_offset) & 0b111111;
        }
      
        //Here we take the 4 sets of 6 apart in 3 sets of 8
        for($i=0; $i<3; $i++) {
          $output = pack('C', $int_24) . $output;
          $int_24 >>= 8;
        }
      }
    
      return $output;
    };
	  throw_if(empty(config('upload.secret')) || empty(config('upload.vi')), \Exaption::class, 'Please run php artisan vendor:publish --provider="Codegor\Upload\Providers\UploadServiceProvider" --tag=config');
    return base64_encode(openssl_encrypt($compress($d), 'AES-256-CBC', base64_decode(config('upload.secret')), false, base64_decode(config('upload.vi'))));
  }
  private static function _decrypt(string $h): string {
    $decompress = function($input, $ascii_offset = 38) {
    
                  $output = '';
        foreach(str_split($input, 3) as $chunk) {
      
          //Reassemble the 24 bit ints from 3 bytes
          $int_24 = 0;
          foreach(unpack('C*', $chunk) as $char) {
            $int_24 <<= 8;
            $int_24 |= $char & 0b11111111;
          }
      
          //Expand the 24 bits to 4 sets of 6, and take their character values
          for($i = 0; $i < 4; $i++) {
            $output = chr($ascii_offset + ($int_24 & 0b111111)) . $output;
            $int_24 >>= 6;
          }
        }
    
        //Make lowercase again and trim off the padding.
        return strtolower(rtrim($output, '='));
    };
	  throw_if(empty(config('upload.secret')) || empty(config('upload.vi')), \Exaption::class, 'Please run php artisan vendor:publish --provider="Codegor\Upload\Providers\UploadServiceProvider" --tag=config');
    return $decompress(openssl_decrypt(base64_decode($h), 'AES-256-CBC', base64_decode(config('upload.secret')), false, base64_decode(config('upload.vi'))));
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