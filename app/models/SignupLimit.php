<?php
/**
 * #PADC#
 * ユーザ登録制限
 */
class SignupLimit extends BaseMasterModel {
	const TABLE_NAME = "padc_signup_limits";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// 制限ステータス
	const LIMIT_STATUS_OVER_LIMIT	= 1;
	const LIMIT_STATUS_NO_LIMIT		= 2;
	
	// ID、人数
	protected static $columns = array(
		'id',			// ID
		'date',			// 対象日
		'num',			// 上限人数
		'last_user_id',	// 最後に登録されたユーザIDをセット
	);

	/**
	 * ユーザ登録上限チェック
	 * @param date $date Y-m-d
	 * @param Redis $rRedis
	 * @param string $openId
	 * @throws PadException
	 */
	static public function checkSignupLimit($date,$rRedis=null,$openId=null)
	{
		if($rRedis === null)
		{
			$rRedis = Env::getRedisForShareRead();
		}

		// 登録上限中でも突破可能なユーザのチェック
		if(Env::CHECK_SIGNUP_LIMIT_DEBUG_USER)
		{
			$debugUsers = array();
			$filePath = Env::DEBUG_USER_FILE_PATH . '/' . Env::SIGNUP_DEBUG_USER_FILE;
			if(file_exists($filePath))
			{
				$tmpDebugUsers = array();
				$debugUserData = file_get_contents($filePath);
				$debugUsers = explode("\n", $debugUserData);
				foreach($debugUsers as $key => $value)
				{
					$value = trim($value);
					if($value)
					{
						$tmpDebugUsers[] = $value;
					}
				}
				$debugUsers = $tmpDebugUsers;
			}
			if(in_array($openId,$debugUsers))
			{
				return;
			}
		}

		// 切り替え時間より前だったら前日扱いとする
		$thishour = Padc_Time_Time::getDate("G") + 0;
		if($thishour < Env::DAY_SWITCHING_HOUR)
		{
			$date = Padc_Time_Time::getDateAdjustDay($date,-1);
		}

		// その日の登録上限に達しているかどうかチェック
		$signupUserLimitkey = RedisCacheKey::getSignupUserLimit($date);
		$signupLimit = $rRedis->get($signupUserLimitkey);
		if($signupLimit === false)
		{
			// 本日の登録上限を取得
			$signupLimitData = self::getSignupLimit($date,$rRedis);
			if($signupLimitData)
			{
				$maxUserId = UserDevice::getMaxUserId();// 現在登録されているIDの最大値
				$signupLimitNum = $signupLimitData['num'];

				// 登録上限が0の場合、その時点で上限エラーとする
				if($signupLimitNum == 0)
				{
					$redis = Env::getRedisForShare();
					$redis->set($signupUserLimitkey,self::LIMIT_STATUS_OVER_LIMIT);
					throw new PadException(RespCode::SIGNUP_LIMIT_ERROR,'signup limit error num is 0 ' . $date);
				}
				else
				{
					$redis = null;
					$baseUserId = 0;
	
					// 前日最後の登録ユーザIDを取得
					$yesterday = Padc_Time_Time::getDateAdjustDay($date,-1);
					$yesterdaySignupLimitData = self::getSignupLimit($yesterday,$rRedis);
					if($yesterdaySignupLimitData !== null)
					{
						if($yesterdaySignupLimitData['last_user_id'] > 0)
						{
							// 前日最後の登録ユーザIDを取得
							$baseUserId = $yesterdaySignupLimitData['last_user_id'];
						}
					}

					if($baseUserId == 0)
					{
						// 前日の最後の登録ユーザがpadc_signup_limitsテーブルから取得できなかったら（レコードが存在しない or IDが0）、
						// share/user_devicesから前日以前に登録されたIDの最大値を取得する
						$yymmddhhiiss = sprintf("%s %02d:%02d:%02d",$date,Env::DAY_SWITCHING_HOUR,0,0);// Y-m-d形式の日付に切り替え時間をH:i:s形式で追加
						$baseUserId = UserDevice::getMaxUserId($yymmddhhiiss);

						// user_devicesから取得したIDが0だった場合、基点のユーザIDを取得
						if($baseUserId == 0)
						{
							$baseUserId = Env::SIGNUP_BASE_USER_ID;
						}

						// 前日分の最後の登録ユーザIDとしてキャッシュに保存しておく
						$signupLimitKey = RedisCacheKey::getSignupLimitKey($yesterday);
						$signupLimitData = array(
							'num'			=> 0,
							'last_user_id'	=> $baseUserId,
						);
						$redis = Env::getRedisForShare();
						$jsonSignupLimitData = json_encode($signupLimitData);
						$redis->set($signupLimitKey,$jsonSignupLimitData);
					}

					$signupUserCount = $maxUserId - $baseUserId;// 「登録されているIDの最大値 - 前日最後にユーザ登録されたユーザのID」から本日の登録人数を算出
		
					// 登録人数が登録上限数を超えたら、キャッシュにセットして専用エラーを返す
					if($signupUserCount > $signupLimitNum)
					{
						if(!$redis)
						{
							$redis = Env::getRedisForShare();
						}
						$redis->set($signupUserLimitkey,self::LIMIT_STATUS_OVER_LIMIT);
						throw new PadException(RespCode::SIGNUP_LIMIT_ERROR,'signup limit error ' . $date);
					}
				}
			}
			else
			{
				// 上限設定なしとしてキャッシュに保存
				$redis = Env::getRedisForShare();
				$redis->set($signupUserLimitkey,self::LIMIT_STATUS_NO_LIMIT);
			}
		}
		else
		{
			// 登録人数が登録上限数を超えていたら専用エラーを返す
			if($signupLimit == self::LIMIT_STATUS_OVER_LIMIT)
			{
				throw new PadException(RespCode::SIGNUP_LIMIT_ERROR,'already signup limit error ' . $date);
			}
			elseif($signupLimit == self::LIMIT_STATUS_NO_LIMIT)
			{
				// 登録制限なし
			}
		}
		return;
	}

	/**
	 * 指定した日付のユーザ登録制限数を取得
	 * @param date $date Y-m-d
	 * @param Redis $rRedis
	 * @return NULL|multitype:number
	 */
	static public function getSignupLimit($date,$rRedis=null)
	{
		if($rRedis === null)
		{
			$rRedis = Env::getRedisForShareRead();
		}

		// キャッシュから指定の日のユーザ登録上限情報を取得
		$key = RedisCacheKey::getSignupLimitKey($date);
		$jsonSignupLimitData = $rRedis->get($key);
		if($jsonSignupLimitData == null)
		{
			$signupLimitData = array(
				'num'			=> 0,
				'last_user_id'	=> 0,
			);

			// 指定の日に登録されたユーザ登録上限情報を取得
			$_cond = array(
				'date' => $date,	
			);
			$signupLimit = SignupLimit::findBy($_cond);
			if($signupLimit)
			{
				$signupLimitData = array(
					'num'			=> $signupLimit->num,
					'last_user_id'	=> $signupLimit->last_user_id,
				);
				$redis = Env::getRedisForShare();
				$jsonSignupLimitData = json_encode($signupLimitData);
				$redis->set($key,$jsonSignupLimitData);
			}
			else
			{
				return null;
			}
		}
		else
		{
			$signupLimitData = json_decode($jsonSignupLimitData,true);
		}
		return $signupLimitData;
	}
}
