<?php
/**
 * 
 * @param string $class
 * @return multitype:string
 */
function tencent_msdk_autoload($class = NULL) {
	static $classes = NULL;
	static $path = NULL;

	if ($classes === NULL) {
		$classes = array(
				'tencent_msdk' => '/Msdk.php',
				'tencent_msdkapi' => '/MsdkApi.php',
				'msdkapiexception' => '/MsdkApiException.php',
				'msdkconnectionexception' => '/MsdkConnectionException.php',
				'snsnetwork' => '/Msdk/TecentLiblrary-3.0.0/SnsNetwork.php',
				'snssigcheck' => '/Msdk/TecentLiblrary-3.0.0/SnsSigCheck.php',
				'tencent_msdk_midas' => '/Msdk/Midas.php',
				'tencent_msdk_qq' => '/Msdk/QQ.php',
				'tencent_msdk_wechat' => '/Msdk/WeChat.php',	
				'tencent_msdk_guest' => '/Msdk/Guest.php',	
		);

		$path = dirname(dirname(__FILE__));
	}

	if ($class === NULL) {
		$result = array(__FILE__);

		foreach ($classes as $file) {
			$result[] = $path . $file;
		}

		return $result;
	}

	$cn = strtolower($class);

	if (isset($classes[$cn])) {
		require $path . $classes[$cn];
	}
}

spl_autoload_register('tencent_msdk_autoload');
