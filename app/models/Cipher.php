<?php

class Cipher {

	/**
	 * 暗号化された文字列を展開して連想配列を返す.
	 * @param string $data
	 * @return array
	 */
	public static function decode($data) {
		$slen = strlen($data);
		assert($slen < (256 * 1024));

		$seed = Cipher::getHexVal(substr($data, 0, 2));
		$csum = Cipher::getHexVal(substr($data, 2, 2)) ^ $seed;

		$indc = 0;
		$ccvv = 0;
		$csumtotal = 0;
		$decode = "";
		for($i = 4; $i < $slen; $i ++) {
			$cd = ord($data[$i]);
			if( $cd == 0 ) {
				break;
			}

			$nc = -1;
			if( (0x30 <= $cd) && ($cd <= 0x39) ) { // '0'～`9’
				$nc = $cd - 0x30; // '0'
			}
			else if( (0x61 <= $cd) && ($cd <= 0x7A) ) { // 'a'～`z’
				$nc = $cd - 0x61 + 10; // 'a'
			}
			else if( (0x41 <= $cd) && ($cd <= 0x5A) ) { // 'A'～`Z’
				$nc = $cd - 0x41 + 36; // 'A'
			}
			else if( $cd == 0x2E ) { // '.'
				$nc = 62;
			}
			else if( $cd == 0x2C ) { // ','
				$nc = 63;
			}

			$seed = ($seed * 0x39 + 0x6d) & 0xFF;
			$nc ^= $seed;
			$nc &= 0x3F;

			if( $indc > 0 ) {
				$ecc = Cipher::getEncCode($nc);
				if( $ecc < 0 ) {
					//izWarning("Invalid triplet code.");
					return null;
				}
				$ccvv = $ccvv * 16 + Cipher::getHexVal1($ecc);
				if( --$indc <= 0 ) {
					$decode .= chr($ccvv);
					$csumtotal += $ccvv;
				}
			}
			else if( $nc == 62 ) {
				$indc = 2;
				$ccvv = 0;
			}
			else {
				$ecc = Cipher::getEncCode($nc);
				if( $ecc >= 0 ) {
					$decode .= chr($ecc);
					$csumtotal += $ecc;
				}
				else {
					//izWarning("Invalid decoded code.");
					return null;
				}
			}
		}
		$csumtotal &= 0xFF;
		if ($csum == $csumtotal) {
			// 連想配列にして返す
			$ret = array();
			$array1 = explode("&",$decode);
			foreach($array1 as $i){
				$array2 = explode("=", $i, 2);
				$ret[$array2[0]] = $array2[1];
			}
			return $ret;
		}
		//izWarning("Unmatch check sum %02x != %02x", csum, (csumtotal & 0xFF));
		return null;
	}

	private static function getHexVal1($cd) {
		if( (0x30 <= $cd) && ($cd <= 0x39) ) { // '0'～'9'
			return $cd - 0x30; // '0'
		}
		return (ord(strtoupper(chr($cd))) - 0x41 + 10); // 'A'
	}

	private static function getHexVal($str) {
		$v = Cipher::getHexVal1(ord($str[0]));
		$v = $v * 16 + Cipher::getHexVal1(ord($str[1]));
		return $v;
	}

	private static function getEncCode($cd) {
		if( $cd < 10 ) {
			return $cd + 0x30; // '0'
		}
		else if( $cd < 36 ) {
			return $cd - 10 + 0x61; // 'a'
		}
		else if( $cd < 62 ) {
			return $cd - 36 + 0x41; // 'A'
		}
		else if( $cd == 63 ) {
			return 0x2C; // ','
		}
		return -1;
	}

	/**
	 * 連想配列を暗号化された文字列に変換して返す.
	 * @param array $params
	 * @return string
	 */
	public static function encode($params) {
		// 引数で渡された連想配列をアプリ側の処理を考慮した文字列に変更します
		// JsonエンコードしたものではNGで、キーと値を「=」でつなげた文字列にします
		$tmp = array();
		foreach ($params as $key => $value) {
			$tmp[] = ($key."=".$value);
		}
		$str = implode("&", $tmp);

		$slen = strlen($str);
		$seed = rand(0,255);

		$csum = 0;
		for($i = 0; $i < $slen; $i ++) {
			$csum += ord($str[$i]);
		}
		$csum &= 0xFF;
		$data = sprintf("%02X%02X", $seed, ($csum^$seed));

		for($i = 0; $i < $slen; $i ++) {
			$cd = ord($str[$i]);
			if ((0x30 <= $cd) && ($cd <= 0x39)) { // '0'～'9'
				$cd = $cd - 0x30 + 0; // '0'
			}
			else if ((0x61 <= $cd) && ($cd <= 0x7A)) { // 'a'～'z'
				$cd = $cd - 0x61 + 10; // 'a'
			}
			else if ((0x41 <= $cd) && ($cd <= 0x5A)) { // 'A'～'Z'
				$cd = $cd - 0x41 + 36; // 'A'
			}
			else if ($cd == 0x2C) { // ','
				$cd = 63;
			}
			else {
				$data .= Cipher::appendBuf(62, $seed);
				$data .= Cipher::appendBuf(($cd >> 4) & 0xF, $seed);
				$data .= Cipher::appendBuf($cd & 0xF, $seed);
				continue;
			}
			$data .= Cipher::appendBuf($cd, $seed);
		}
		return $data;
	}

	private static function appendBuf($nc, &$seed) {
		$seed = ($seed * 0x39 + 0x6d) & 0xFF;
		$nc ^= $seed;
		$nc &= 0x3F;

		if ($nc < 10) {
			$nc = $nc + 0x30; // '0'
		}
		else if ($nc < 36) {
			$nc = $nc - 10 + 0x61; // 'a'
		}
		else if ($nc < 62) {
			$nc = $nc - 36 + 0x41; // 'A'
		}
		else if ($nc == 62) {
			$nc = 0x2E; // '.'
		}
		else {
			$nc = 0x2C; // ','
		}

		return chr($nc);
	}

}
