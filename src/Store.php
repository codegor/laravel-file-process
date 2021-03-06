<?php
namespace Codegor\Upload;

use Storage;
use Auth;
use ZipArchive;
use Exception;

class Store {
	public static $availableSpace = false;
	public static $prefixLink = '/file/';
	
	public static function setAvailableSpace(int $bites):void {
		self::$availableSpace = $bites;
	}
	public static function setPrefixLink(string $path):void  {
		self::$prefixLink = $path;
	}
	
	public static function uploadFileRndName(string $ext, string $data, bool $accessPrivate = false): string {
		$ext = strtolower($ext);
		$name = md5(substr($data,0,32).time());
		return self::uploadFile($name, $ext, $data, $accessPrivate);
	}
	
	public static function uploadFile(string $name, string $ext, string $data, bool $accessPrivate = false): string {
		if (!self::_checkExt($ext)) return '';
		if (!self::_checkLimit($data)) return '';
		
		$name .= ".$ext";
		$user_id = Auth::user()->id;
		$path = "files/$user_id";
		$path = self::_checkCountFiles($path);
		if (Storage::put("$path/$name", base64_decode($data)))
			return self::$prefixLink.( $accessPrivate ? File::getFileUrl("$path/$name") : File::getSharedFileUrl("$path/$name"));
		else
			return '';
	}
	
	public static function createZip(array $list):string {
		if (!is_array($list)) throw new Exception('createZip: list params should be an array');
		$user_id = Auth::user()->id;
		$path = "files/zips/$user_id/";
		$path = self::_checkCountFiles($path);
		Storage::makeDirectory($path);
		$path .= md5($user_id . json_encode($list) . time()) . '.zip';
		$zip = new ZipArchive();
		$result_code = $zip->open(storage_path("app/$path"), ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
		if ($result_code !== true) {
			$ZIP_ERROR = [
				ZipArchive::ER_EXISTS => 'File already exists.',
				ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
				ZipArchive::ER_INVAL  => 'Invalid argument.',
				ZipArchive::ER_MEMORY => 'Malloc failure.',
				ZipArchive::ER_NOENT  => 'No such file.',
				ZipArchive::ER_NOZIP  => 'Not a zip archive.',
				ZipArchive::ER_OPEN   => "Can't open file.",
				ZipArchive::ER_READ   => 'Read error.',
				ZipArchive::ER_SEEK   => 'Seek error.',
			];
			$msg = isset($ZIP_ERROR[$result_code]) ? $ZIP_ERROR[$result_code] : 'Unknown error.';
			throw new Exception('createZip: cannot open file - ' . $msg);
		}
		foreach ($list as $obj) {
			if (!(isset($obj->link) && isset($obj->name) && isset($obj->type)))
				throw new Exception('createZip: obj item have wrong srtucture');
			
			if (file_exists(storage_path('app/' . $obj->link)))
				$zip->addFile(storage_path('app/' . $obj->link), "$obj->name.$obj->type");
		}
		$zip->close();
		return self::$prefixLink.File::getFileUrl($path);
	}
	
	public static function checkUrl(string $url): bool {
		$url = substr($url, strlen(self::$prefixLink));
		return !!File::getFilePath($url);
	}
	
	private static function _checkExt(string $ext): bool {
		return in_array($ext, explode(',', config('upload.extantion')));
	}
	
	private static function _checkLimit(string $data): bool {
		if(self::$availableSpace)
			return strlen(base64_decode($data)) <= self::$availableSpace;
		else
			return true;
	}
	
	private static function _checkCountFiles(string $path, int $count = 0): string {
		if (5000 <= count(Storage::files($path . (0 != $count ? '/' . $count : '')))) {
			$count++;
			return self::_checkCountFiles($path, $count);
		} else
			return $path . (0 != $count ? '/' . $count : '');
	}
}