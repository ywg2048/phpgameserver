<?php
/**
 * ban ngword when check player's name or player's email
 *
 * @author zhudesheng
 *
 */
class NgWord extends BaseMasterModel{

	CONST TABLE_NAME = 'padc_ngwords';
	CONST MEMCACHED_EXPIRE = 86400;
	CONST NGNAME = 1;
	CONST NGMAIL = 2;

	protected static $columns = array(
			'id',
			'ngword',
			'ngname',
			'ngmail'
	);

	/**
	 * check ngword
	 * @param string $user_input
	 * @param int $type
	 * @return string|NULL if no ng word,return null,else return the ngword
	 */
	public static function checkNGWords($user_input,$type=NgWord::NGNAME){
		$user_input = self::trimall($user_input);//trim blank space \t \n and so on
		if($type == NgWord::NGNAME){
			$words = self::getNGName();
		}else{
			$words = self::getNGMail();
		}

		foreach ($words as $word){
			if(mb_stripos($user_input, $word, 0, 'utf-8') !== false){
				return $word;
			}
		}
		return null;
	}

	/**
	 * trim all blank \t \n \r
	 * @param string $str
	 * @return string
	 */
	public static function trimall($user_input)
	{
		$search=array(" ","　","\t","\n","\r");$replace=array("","","","","");
		return str_replace($search,$replace,$user_input);
	}

	/**
	 * get ngword lists for player name
	 * @throws PadException  ,throw Padexception when padc_ngword_lists table have no data
	 * @return array  ,the array contain all ngname
	 */
	public static function getNGName(){
		$rRedis = Env::getRedisForShareRead();
		$key = CacheKey::getNgNameLists();
		$words = apc_fetch($key);
		if(!$words){
			$words = $rRedis->get($key);
			if (!$words){
				$pdo = Env::getDbConnectionForShareRead();
				$values = self::findAllBy(array("ngname"=>1),null,null,$pdo);
				if($values){
					foreach ($values as $value){
						$words[] = $value->ngword;
					}
				}else{
					$words = array();
				}

				$redis = Env::getRedisForShare();
				$redis->set($key,$words,static::MEMCACHED_EXPIRE);
			}
			apc_store($key, $words,static::MEMCACHED_EXPIRE + static::add_apc_expire());
		}
		return $words;
	}

	/**
	 * get ngword lists for player email
	 * @throws PadException
	 * @return array  ,the array contain all ngwords
	 */
	public static function getNGMail(){
		$rRedis = Env::getRedisForShareRead();
		$key = CacheKey::getNgMailLists();
		$words = apc_fetch($key);
		if(!$words){
			$words = $rRedis->get($key);
			if (!$words){
				$pdo = Env::getDbConnectionForShareRead();
				$values = self::findAllBy(array("ngmail"=>1),null,null,$pdo);
				if($values){
					foreach ($values as $value){
						$words[] = $value->ngword;
					}
				}else{
					$words = array();
				}

				$redis = Env::getRedisForShare();
				$redis->set($key,$words,static::MEMCACHED_EXPIRE);
			}
			apc_store($key, $words,static::MEMCACHED_EXPIRE + static::add_apc_expire());
		}
		return $words;
	}
}
