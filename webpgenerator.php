<?php
/* 
Simple WEBP generator script by marketingtracer.com.

MIT License

Copyright (c) 2020 MarketingTracer

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

USAGE: 
1. Upload any jpg,jpeg on png file (file.png) to the webserver. 
2. Interchange the file extension to webp (file.webp) or append .webp extension (file.png.webp) and check the WEBP_EXTENSTION_METHOD below
3. Select the conversion method. CWEBP creates smaller files but relies on the webp package and is not available by default. GD should be supported in most cases
4. Rewrite all non existent webp files to /<PATH TO>/webpgenerator.php using the config below:


NGINX CONFIG:    
location ~* \.webp$ {
  try_files $uri /path-to/webpgenerator.php;
}


APACHE CONFIG:
<IfModule mod_rewrite.c>
  RewriteEngine on
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_URI} \.(webp)$
  RewriteRule ^/?(.+)\.(webp)$ /path-to/webp-generator.php [QSA,L]
</IfModule>

*/


define( "WEBP_METHOD", "CWEBP" ); // GD || CWEBP
define( "WEBP_EXTENSTION_METHOD", 'INTERCHANGE' );  // 'INTERCHANGE' || 'APPEND'
define( "WEBP_WRITE_TO_DISK", true );  // true || false
define( "WEBP_QUALITY", 75 );  // true || false

WebPConvert::convert($_SERVER['REQUEST_URI']);

class WebPConvert{
	public static function convert($file, $compression_quality = 80){
		//remove qs
		$file = strtok($file, '?');

		// get path info
		$path = pathinfo($file);

		// append extension or not (not, in that case guess the original file)
		$aFile = (WEBP_EXTENSTION_METHOD == 'INTERCHANGE')?self::_get_filename_interchanged_extension($path):self::_get_filename_appended_extension($path);

		// convert
		self::_doconvert($aFile);
	}

	private static function _get_filename_appended_extension($path){
		// webp is in format img.jpg.webp
		$orig_abspath = $_SERVER['DOCUMENT_ROOT'].$path['dirname'].'/'.$path['filename'];
		$webp_abspath = $_SERVER['DOCUMENT_ROOT'].$path['dirname'].'/'.pathinfo($path['filename'], PATHINFO_FILENAME).'.webp';


		return(['orig_abspath'=>$orig_abspath,'webp_abspath'=>$webp_abspath]);
	}

	private static function _get_filename_interchanged_extension($path){
		// webp is in format img.webp we need to check if original jpg, jpeg and png exist
		$aExt = ['png','jpg','jpeg'];

		$webp_abspath = $_SERVER['DOCUMENT_ROOT'].$path['dirname'].'/'.$path['filename'].'.webp';

		foreach ($aExt as $ext) {
			$img_path_to_try = $_SERVER['DOCUMENT_ROOT'].$path['dirname'].'/'.$path['filename'].'.'.$ext;
			if(file_exists($img_path_to_try)){
				return ['orig_abspath'=>$img_path_to_try,'webp_abspath'=>$webp_abspath];
			} 
		}

		// cant help you buddy
		return false;
	}

	private static function _doconvert($aFile){
		$mime = mime_content_type ( $aFile['orig_abspath'] );
		$x = (WEBP_METHOD === 'GD')?self::_doconvert_gd($aFile,$mime):self::_doconvert_cwebp($aFile,$mime);
	}	


	private static function _doconvert_cwebp($aFile,$mime){
		// shell exec cwebp
		if(WEBP_WRITE_TO_DISK){
			header('Content-type:image/webp');
			echo shell_exec('cwebp '.$aFile['orig_abspath'].' -q '.WEBP_QUALITY.' -quiet -o '.$aFile['webp_abspath']);
			header('Content-type:image/webp');
				echo readfile($aFile['webp_abspath']);
		} else {
			header('Content-type:image/webp');
			echo shell_exec('cwebp '.$aFile['orig_abspath'].' -q '.WEBP_QUALITY.' -quiet -o -');
		}
		exit();
	}


	private static function _doconvert_gd($aFile,$mime){

		// use gd library
		switch ($mime) {
			case 'image/jpeg':
			case 'image/jpg':
			$image = imagecreatefromjpeg($aFile['orig_abspath']);
			break;

			case 'image/png':
			$image = imagecreatefrompng($aFile['orig_abspath']);
			imagepalettetotruecolor($image);
			imagealphablending($image, true);
			imagesavealpha($image, true);
			break;
		}

		if(WEBP_WRITE_TO_DISK){
			$result = imagewebp($image,$aFile['webp_abspath'],WEBP_QUALITY);
			imagedestroy($image);

			if(!$result){
				header('Content-type:'.$mime);
				readfile($aFile['orig_abspath']);
			} else {
				header('Content-type:image/webp');
				echo readfile($aFile['webp_abspath']);
			}
		} else {
			header('Content-type:image/webp');
			$result = imagewebp($image);
		}
		
		exit();
	}
}
