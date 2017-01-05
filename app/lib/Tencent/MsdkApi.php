<?php

/**
 * #PADC#
 * Msdk server API
 */
require_once (LIB_DIR . '/Tencent/Msdk/Autoload.php');
class Tencent_MsdkApi {
	const SCORE_TYPE_LEVEL = 1;
	const SCORE_TYPE_GOLD = 2;
	const SCORE_TYPE_RANKING = 3;
	const SCORE_TYPE_LOGIN = 4;
	const SCORE_TYPE_DEVICE_TYPE = 5;
	const SCORE_TYPE_SIGNUP = 6;
	const SCORE_TYPE_PURCHASE = 7;
	const SCORE_TYPE_CARDS = 8;
	const RETRY_TIMES = 5;
        const SCORE_TYPE_PRIVILEDGE_STARTUP = 9;
        const SCORE_TYPE_TEAM_POWER = 10;
        const SCORE_TYPE_IP_HARD = 11;
        const SCORE_TYPE_IP_NORMAL = 12;
        const SCORE_TYPE_IP_EASY = 13;
        const SCORE_TYPE_IP_GACHA_SINGLE = 14;
        const SCORE_TYPE_IP_GACHA_TEN = 15;
        const SCORE_TYPE_VIP_LEVEL = 16;
        const SCORE_TYPE_IP_HELL = 17;//地狱级
        const SCORE_TYPE_IP_SUPERHELL = 18;//超级地狱级
	
	/**
	 * チェック　パラメータ
	 *
	 * @param array $params        	
	 * @return boolean|array
	 */
	public static function checkToken($params) {
		$token = array ();
		
		$token ['access_token'] = isset ( $params ['ten_at'] ) ? $params ['ten_at'] : null;
		$token ['pay_token'] = isset ( $params ['ten_pt'] ) ? $params ['ten_pt'] : null;
		$token ['pf'] = isset ( $params ['ten_pf'] ) ? $params ['ten_pf'] : null;
		$token ['pfkey'] = isset ( $params ['ten_pfk'] ) ? $params ['ten_pfk'] : null;
		
		// INFO:リクエストパラメータから処理を行うか切り替えられるように対応
		$token ['check_tencent'] = isset ( $params ['check_tencent'] ) ? $params ['check_tencent'] : null;
		if (Env::CHECK_TENCENT_TOKEN || $token ['check_tencent'] != null) {
			foreach ( $token as $key => $value ) {
				if (! ($value || $key == 'pay_token' || $key == 'check_tencent' || $key == 'pf' || $key == 'pfkey')) {
					if (Env::ENV !== "production") {
						global $logger;
						$logger->log ( 'Parameter not found : (' . $key . ')', Zend_log::ERR );
					}
					return false;
				}
			}
		}
		return $token;
	}
	
	/**
	 * Verify user login status
	 *
	 * @param string $open_id        	
	 * @param string $access_token        	
	 * @param string $userip
	 *        	User IP address. Necessary for QQ user.
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function verifyLogin($open_id, $access_token, $userip, $platform_type = UserDevice::PTYPE_QQ) {
		if ($platform_type == UserDevice::PTYPE_QQ) {
			return Tencent_Msdk_QQ::verifyQqLogin ( $open_id, $access_token, $userip );
		} else if ($platform_type == UserDevice::PTYPE_WECHAT) {
			return Tencent_Msdk_WeChat::checkWeChatToken ( $open_id, $access_token );
		} else if ($platform_type == UserDevice::PTYPE_GUEST) {
			return Tencent_Msdk_Guest::verifyGuestLogin ( $open_id, $access_token );
		} else {
			return false;
		}
	}
	
	/**
	 * Get balance
	 *
	 * @param string $openid        	
	 * @param array $token        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function getBalance($openid, $token, $platform_type = UserDevice::PTYPE_QQ, $type = UserDevice::TYPE_ADR) {
		return Tencent_Msdk_Midas::getBalance ( $openid, $token ['access_token'], $token ['pay_token'], $token ['pf'], $token ['pfkey'], $platform_type, $type );
	}
	
	/**
	 * Pay gold
	 *
	 * @param number $pay_num        	
	 * @param string $openid        	
	 * @param array $token        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function payGold($pay_num, $openid, $token, $platform_type = UserDevice::PTYPE_QQ, $type = UserDevice::TYPE_ADR, $billno = null, $retry_times = self::RETRY_TIMES) {
		try {
			return Tencent_Msdk_Midas::payGold ( $pay_num, $openid, $token ['access_token'], $token ['pay_token'], $token ['pf'], $token ['pfkey'], $platform_type, $type, $billno );
		} catch ( Exception $e ) {
			if ($e instanceof MsdkApiException) {
				$errorCode = $e->getCode ();
				if ($errorCode == MsdkApiException::ERROR_TRY_LATER) {
					$result = $e->getResult ();
					$billno = $result ['billno'];
					if ($retry_times > 0) {
						// Midas 3000111 error, try again with billno
						return self::payGold ( $pay_num, $openid, $token, $platform_type, $type, $billno, $retry_times - 1 );
					} else {
						// try too many times, return error.
						throw $e;
					}
				} else if ($errorCode == MsdkApiException::ERROR_DUPLICATE) {
					// already successed
					$result = $e->getResult ();
					$billno = $result ['billno'];
					return array (
							'ret' => 0,
							'billno' => $billno 
					);
				} else {
					// other MsdkApiException
					throw $e;
				}
			} else {
				// MsdkConnectionException
				throw $e;
			}
		}
	}
	
	/**
	 * Cencel pay
	 *
	 * @param number $cancel_num        	
	 * @param string $openid        	
	 * @param array $token        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function cancelPay($cancel_num, $billno, $openid, $token, $platform_type = UserDevice::PTYPE_QQ, $type = UserDevice::TYPE_ADR, $retry_times = self::RETRY_TIMES) {
		try {
			return Tencent_Msdk_Midas::cancelPay ( $cancel_num, $billno, $openid, $token ['access_token'], $token ['pay_token'], $token ['pf'], $token ['pfkey'], $platform_type, $type );
		} catch ( Exception $e ) {
			if ($e instanceof MsdkApiException) {
				$errorCode = $e->getCode ();
				if ($errorCode == MsdkApiException::ERROR_TRY_LATER) {
					$result = $e->getResult ();
					$billno = $result ['billno'];
					if ($retry_times > 0) {
						// Midas 3000111 error, try again with billno
						return self::cancelPay ( $cancel_num, $billno, $openid, $token, $platform_type, $type, $retry_times - 1 );
					} else {
						// try too many times, return error.
						throw $e;
					}
				} else if ($errorCode == MsdkApiException::ERROR_DUPLICATE) {
					// already successed
					$result = $e->getResult ();
					$billno = $result ['billno'];
					return array (
							'ret' => 0,
							'billno' => $billno 
					);
				} else {
					// other MsdkApiException
					throw $e;
				}
			} else {
				// MsdkConnectionException
				throw $e;
			}
		}
	}
	
	/**
	 * Query qualify
	 *
	 * @param string $openid        	
	 * @param array $token        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function queryQualify($openid, $token, $platform_type, $type = UserDevice::TYPE_ADR) {
		return Tencent_Msdk_Midas::queryQualify ( $openid, $token ['access_token'], $token ['pay_token'], $token ['pf'], $token ['pfkey'], $platform_type, $type );
	}
	
	/**
	 * free gold present
	 *
	 * @param string $openid        	
	 * @param array $token        	
	 * @param int $platform_type        	
	 * @param int $num        	
	 * @param int $discountid        	
	 * @param int $presentid        	
	 * @return array
	 */
	public static function present($openid, $token, $platform_type, $type = UserDevice::TYPE_ADR, $num, $discountid = NULL, $presentid = NULL, $billno = null, $retry_times = self::RETRY_TIMES) {
		try {
			return Tencent_Msdk_Midas::present ( $openid, $token ['access_token'], $token ['pay_token'], $token ['pf'], $token ['pfkey'], $platform_type, $type, $num, $discountid, $presentid, $billno );
		} catch ( Exception $e ) {
			if ($e instanceof MsdkApiException) {
				$errorCode = $e->getCode ();
				if ($errorCode == MsdkApiException::ERROR_TRY_LATER) {
					$result = $e->getResult ();
					$billno = $result ['billno'];
					if ($retry_times > 0) {
						// Midas 3000111 error, try again with billno
						return self::present ( $openid, $token, $platform_type, $type, $num, $discountid, $presentid, $billno, $retry_times - 1 );
					} else {
						// try too many times, return error.
						throw $e;
					}
				} else if ($errorCode == MsdkApiException::ERROR_DUPLICATE) {
					// already successed
					return array (
							'ret' => 0 
					);
				} else {
					// other MsdkApiException
					throw $e;
				}
			} else {
				// MsdkConnectionException
				throw $e;
			}
		}
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param array $token        	
	 * @param int $platform_type        	
	 */
	public static function querySubscribe($openid, $token, $platform_type = UserDevice::PTYPE_QQ, $type = UserDevice::TYPE_ADR) {
		return Tencent_Msdk_Midas::querySubscribe ( $openid, $token ['access_token'], $token ['pay_token'], $token ['pf'], $token ['pfkey'], $platform_type, $type );
	}
	
	/**
	 *
	 * @param number $buy_quantity        	
	 * @param string $openid        	
	 * @param array $token        	
	 * @param number $platform_type        	
	 */
	public static function presentSubscribe($buy_quantity, $openid, $token, $platform_type = UserDevice::PTYPE_QQ, $type = UserDevice::TYPE_ADR) {
		return Tencent_Msdk_Midas::presentSubscribe ( $openid, $token ['access_token'], $token ['pay_token'], $token ['pf'], $token ['pfkey'], $platform_type, $type, $buy_quantity );
	}
	
	/**
	 * get friends openid
	 *
	 * @param string $openid        	
	 * @param int $ptype        	
	 * @param array $token        	
	 * @return array:$openids
	 */
	public static function getFriendsOpenIds($openid, $ptype, $token) {
		$openids = array ();
		if ($ptype == UserDevice::PTYPE_QQ) {
			$result = Tencent_Msdk_QQ::getQQFriendsDetail ( $openid, $token ['access_token'], $token ['pf'] );
			$lists = isset ( $result ['lists'] ) ? $result ['lists'] : array ();
			foreach ( $lists as $friend ) {
				$openids [] = $friend ['openid'];
			}
		} else if ($ptype == UserDevice::PTYPE_WECHAT) {
			$result = Tencent_Msdk_WeChat::getwxfriends ( $openid, $token ['access_token'] );
			$openids = isset ( $result ['openids'] ) ? $result ['openids'] : array ();
		} else {
			return array ();
		}
		
		// remove my openid from array
		$result = array_diff ( $openids, array (
				$openid 
		) );
		return array_values ( $result );
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param number $score        	
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportRankingScore($openid, $score, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_RANKING,
							'bcover' => 0,
							'data' => '' . $score,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		} else if ($ptype == UserDevice::PTYPE_WECHAT) {
			// return Tencent_Msdk_WeChat::wxBattleReport ( $openid, $score );
		}
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param number $level        	
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportLevel($openid, $level, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_LEVEL,
							'bcover' => 1,
							'data' => '' . $level,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_RANKING,
							'bcover' => 0,
							'data' => '' . $level,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param number $gold        	
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportGold($openid, $gold, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_MONEY,
							'bcover' => 1,
							'data' => '' . $gold,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param number $loginTime        	
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportLogin($openid, $loginTime, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_LOGIN_TIME,
							'bcover' => 1,
							'data' => '' . $loginTime,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param number $signupTime        	
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportSignup($openid, $signupTime, $clear_cnt, $gold, $card_num, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_SIGNUP_TIME,
							'bcover' => 1,
							'data' => '' . $signupTime,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_LOGIN_TIME,
							'bcover' => 1,
							'data' => '' . $signupTime,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_LEVEL,
							'bcover' => 1,
							'data' => '' . $clear_cnt,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_RANKING,
							'bcover' => 0,
							'data' => '' . $clear_cnt,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_MONEY,
							'bcover' => 1,
							'data' => '' . $gold,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_PURCHASE,
							'bcover' => 1,
							'data' => '' . 0,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_CARDS,
							'bcover' => 1,
							'data' => '' . $card_num,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param number $gold        	
	 * @param number $addGold        	
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportPurchase($openid, $gold, $addGold, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_PURCHASE,
							'bcover' => 1,
							'data' => '' . $addGold,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_MONEY,
							'bcover' => 1,
							'data' => '' . $gold,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

        /**
	 *
	 * @param string $openid        	
	 * @param number $card_sum        	
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportCards($openid, $card_sum, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_CARDS,
							'bcover' => 1,
							'data' => '' . $card_sum,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

	/**
	 *
	 * @param string $openid        	
	 * @param number $time        	
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportPriviledgeStartup($openid, $time, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_PRIVILEDGE_STARTUP,
							'bcover' => 1,
							'data' => '' . $time,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

	/**
	 *
	 * @param string $openid        	
	 * @param number $power
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportTeamPower($openid, $power, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_TEAM_POWER,
							'bcover' => 1,
							'data' => '' . $power,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

        /**
	 *
	 * @param string $openid        	
	 * @param number $vipLv
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportVipLevel($openid, $vipLv, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_VIP_LEVEL,
							'bcover' => 1,
							'data' => '' . $vipLv,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

   /**
	 *
	 * @param string $openid        	
	 * @param number $count
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 * @author YWG
	 */
	public static function reportIpSuperHellPlayedCount($openid, $count, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_IP_SUPERHELL,
							'bcover' => 1,
							'data' => '' . $count,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}
	
   /**
	 *
	 * @param string $openid        	
	 * @param number $count
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 * @author YWG
	 */
	public static function reportIpHellPlayedCount($openid, $count, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_IP_HELL,
							'bcover' => 1,
							'data' => '' . $count,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

        /**
	 *
	 * @param string $openid        	
	 * @param number $count
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportIpHardPlayedCount($openid, $count, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_IP_HARD,
							'bcover' => 1,
							'data' => '' . $count,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

	/**
	 *
	 * @param string $openid        	
	 * @param number $count
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportIpNormalPlayedCount($openid, $count, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_IP_NORMAL,
							'bcover' => 1,
							'data' => '' . $count,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

	/**
	 *
	 * @param string $openid        	
	 * @param number $count
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportIpEasyPlayedCount($openid, $count, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_IP_EASY,
							'bcover' => 1,
							'data' => '' . $count,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

	/**
	 *
	 * @param string $openid        	
	 * @param number $count
	 * @param number $type        	
	 * @param number $ptype        	
	 * @param string $token        	
	 * @return array
	 */
	public static function reportGachaSingleCount($openid, $count, $type, $ptype, $token) {
		if ($ptype == UserDevice::PTYPE_QQ) {
			$param = array (
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_IP_GACHA_SINGLE,
							'bcover' => 1,
							'data' => '' . $count,
							'expires' => '0' 
					),
					array (
							'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
							'bcover' => 1,
							'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
							'expires' => '0' 
					) 
			);
			return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
		}
	}

        /**
         *
         * @param string $openid
         * @param number $count
         * @param number $type
         * @param number $ptype
         * @param string $token
         * @return array
         */
        public static function reportGachaTenCount($openid, $count, $type, $ptype, $token) {
                if ($ptype == UserDevice::PTYPE_QQ) {
                        $param = array (
                                        array (
                                                        'type' => Tencent_Msdk_QQ::QQ_SCORE_IP_GACHA_TEN,
                                                        'bcover' => 1,
                                                        'data' => '' . $count,
                                                        'expires' => '0'
                                        ),
                                        array (
                                                        'type' => Tencent_Msdk_QQ::QQ_SCORE_DEVICE_TYPE,
                                                        'bcover' => 1,
                                                        'data' => ($type == UserDevice::TYPE_ADR) ? '1' : '2',
                                                        'expires' => '0'
                                        )
                        );
                        return Tencent_Msdk_QQ::qqScoreBatch ( $openid, $token ['access_token'], $param );
                }
        }

	/**
	 *
	 * @param string $openid        	
	 * @param string $token        	
	 * @param int $platform_type        	
	 * @return array
	 */
	public static function getVipInfo($openid, $token, $platform_type) {
		if ($platform_type == UserDevice::PTYPE_QQ) {
			// return Tencent_Msdk_QQ::getQQVip($openid, $token ['access_token'], Tencent_Msdk_QQ::VIP_NORMAL + Tencent_Msdk_QQ::VIP_SUPER);
			return Tencent_Msdk_QQ::getVipRichInfo ( $openid, $token ['access_token'] );
		} else {
			return false;
		}
	}
	
	/**
	 *
	 * @param string $openid        	
	 * @param array $token        	
	 * @param string $userip        	
	 * @param array $fopenids        	
	 * @param number $platform_type        	
	 * @return Ambigous array|boolean
	 */
	public static function getFriendsVipInfo($openid, $token, $userip, $fopenids, $platform_type) {
		if ($platform_type == UserDevice::PTYPE_QQ) {
			$foids_list = array_chunk ( $fopenids, Tencent_Msdk_QQ::QQ_FRIENDS_VIP_LIMIT );
			$fvips = array ();
			$is_lost = false;
			foreach ( $foids_list as $foids ) {
				$res = Tencent_Msdk_QQ::getQQFriendsVip ( $openid, $token ['access_token'], $foids, $userip );
				if ($res ['ret'] != 0) {
					return $res;
				}
				$fvips = array_merge ( $fvips, $res ['lists'] );
				$is_lost = $is_lost | $res ['is_lost'];
			}
			return array (
					
					// 'ret' => 0,
					'lists' => $fvips,
					'is_lost' => $is_lost 
			);
		} else {
			return false;
		}
	}
}
