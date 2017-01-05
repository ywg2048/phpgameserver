<?php

/**
 * #PADC#
 * 暗号化関連処理クラス
 */
class Padc_Encrypt
{
	const ENCRYPT_KEY = '1234567890abcdef';
	const ENCRYPT_IV  = '1234567890abcdef';

	/**
	 * 暗号化
	 * @param string $text
	 * @return 
	 */
	public static function encrypt($text)
	{
		// 指定した暗号のブロックサイズを得る
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		// PKCS5Padding ブロック長に満たないサイズを埋める
		$text = self::pkcs5_pad($text, $size);

		$resource = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
		mcrypt_generic_init($resource, self::ENCRYPT_KEY, self::ENCRYPT_IV);
		$encrypted = mcrypt_generic($resource, $text);
		mcrypt_generic_deinit($resource);
		mcrypt_module_close($resource);

		$base64_encrypted_data = base64_encode($encrypted);
		$base64_encrypted_data = str_replace(array("/","+","="), array("_","-",","), $base64_encrypted_data);
			
		return $base64_encrypted_data;
	}

	/**
	 * 複合化
	 * @param string $base64_encrypted_data
	 * @return 
	 */
	public static function decrypt($base64_encrypted_data)
	{
		if(!$base64_encrypted_data)
		{
			return '';
		}

		$base64_encrypted_data = str_replace(array("_","-",","), array("/","+","="), $base64_encrypted_data);
		$encrypted = base64_decode($base64_encrypted_data);

		$resource = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
		mcrypt_generic_init($resource, self::ENCRYPT_KEY, self::ENCRYPT_IV);
		$text = mdecrypt_generic($resource, $encrypted);
		mcrypt_generic_deinit($resource);
		mcrypt_module_close($resource);

		// 指定した暗号のブロックサイズを得る
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		// PKCS5Padding 埋められたバイト値を除く
		$text = self::pkcs5_unpad($text,$size);

		return $text; 
	}

	/**
	 * PKCS5Padding
	 * ブロック長に満たないサイズを埋める
	 * @param string $text
	 * @param int $blocksize
	 * @return string
	 */
	public static function pkcs5_pad($text, $blocksize)
	{
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);		
	}

	/**
	 * PKCS5Padding
	 * 埋められたバイト値を除く
	 * @param string $text
	 * @return string
	 */
	public static function pkcs5_unpad($text)
	{
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
		return substr($text, 0, -1 * $pad);
	}
}
