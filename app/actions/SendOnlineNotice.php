<?php
/**
*防沉迷接口
***/
class SendOnlineNotice extends BaseAction {
	const MSG_TYPE_GET_CONF = 1;//拉取游戏配置
	const MSG_TYPE_GET_USERINFO_SINGLE = 2;//获取用户防沉迷信息
	const MSG_TYPE_UPDATE_USERINFO_SINGLE = 3;//上报用户在线
	const MSG_TYPE_PUSH_EDNGAME = 6;//用户退出游戏
	const MSG_TYPE_REPORT_REMINDED_BATCH = 8;//弹窗提醒
	const MAX_ONLINE_TIME = 8;//最大累计在线时长，小时
	const MAX_SIGLE_ONLINE_TIME = 3;//最大单次在线时长，小时

	public function action($params) {
		return json_encode ( array (
					'res' => RespCode::SUCCESS,
					'is_need_reminded' => 0
			) );
		//11月底打开
		global $logger;
	try{
				$open_id = UserDevice::getUserOpenId($params['pid']);
			}catch (PadException $e) {
				//没有找到openid,设为pid
				$open_id = $params['pid'];
			}
		
		
		$logger->log("open_id = ".$open_id ,Zend_Log::DEBUG);

		$pdo = Env::getDbConnectionForUserRead($params['pid']);
		$user_online_time = UserOnlineTime::findBy(array('pid'=>$params['pid']),$pdo);
		if(!$user_online_time){
			//没有记录则新建
			$user_online_time = new UserOnlineTime();
			$user_online_time->pid = $params['pid'];
			$user_online_time->accumulate_time = 300;
			$user_online_time->last_report_time = User::timeToStr(time());
			$user_online_time->is_alert = 0;
			$user_online_time->create($pdo);
		}else{
			//有记录则需要判断是否要累计，不累计则置为0
			$body_info_getinfo = array(
				"account_id" => $open_id,
        "character_id" => "",
			);

			$get_userinfo = CheckOnlineNotice::OnlineNoticeApi($body_info_getinfo,self::MSG_TYPE_GET_USERINFO_SINGLE);
			$res = json_decode($get_userinfo,1);

			if(time() - strtotime($user_online_time->last_report_time)>600){
				$user_online_time->pid = $params['pid'];
				$user_online_time->accumulate_time = 0;
				$user_online_time->last_report_time = User::timeToStr(time());
				if(isset($res['user_info']['accumu_time'])){
					if($res['user_info']['accumu_time']<10800){
						$user_online_time->is_alert = 0;
					}
				}
				$user_online_time->update($pdo);
			}else{
				$user_online_time->pid = $params['pid'];
				$user_online_time->accumulate_time = (int)$user_online_time->accumulate_time+300;
				$user_online_time->last_report_time = User::timeToStr(time());
				if(isset($res['user_info']['accumu_time'])){
					if($res['user_info']['accumu_time']<10800){
						$user_online_time->is_alert = 0;
					}
				}
				$user_online_time->update($pdo);
			}
		}
		
		
		if($params['msg_type'] == self::MSG_TYPE_UPDATE_USERINFO_SINGLE){
			//上报用户在线状态
			$body_info = array(
				"account_id" => $open_id,
        "character_id" => "",
        "this_period_time" => 300
			);
			$update_userinfo = CheckOnlineNotice::OnlineNoticeApi($body_info,self::MSG_TYPE_UPDATE_USERINFO_SINGLE);
			$res = json_decode($update_userinfo,1);
			if($res == null){
				return json_encode(array(
					'res' => RespCode::UNKNOWN_ERROR
				));
			}
			if($res["comm_rsp"]['ret'] == 0){
				$logger->log($res['comm_rsp']['err_msg'],Zend_Log::DEBUG);
				$logger->log("在线时长 = ".$res['user_info']['accumu_time'],Zend_Log::DEBUG);
				//查询单次在线时长
				$user_online_time = UserOnlineTime::findBy(array('pid'=>$params['pid']),$pdo);
				if($user_online_time->accumulate_time>=10800 && $user_online_time->is_alert == 0){
					return json_encode ( array (
						'res' => RespCode::SUCCESS,
						'is_need_reminded' => 1,
						'accumu_time' => $res["user_info"]["accumu_time"]
					) );
				}
				return json_encode ( array (
					'res' => RespCode::SUCCESS,
					'is_need_reminded' => $res["user_info"]['is_need_reminded'],
					'accumu_time' => $res["user_info"]["accumu_time"]
				) );
			}
			return json_encode ( array (
					'res' => RespCode::SUCCESS,
					'is_need_reminded' => 0
			) );
		}elseif ($params['msg_type'] == self::MSG_TYPE_REPORT_REMINDED_BATCH) {
			//弹窗提醒
			//首先查询用户的防沉迷信息
			$body_info_getinfo = array(
				"account_id" => $open_id,
        "character_id" => "",
			);

			$get_userinfo = CheckOnlineNotice::OnlineNoticeApi($body_info_getinfo,self::MSG_TYPE_GET_USERINFO_SINGLE);
			$res = json_decode($get_userinfo,1);

			$report_type = 1;
			if(isset($res['user_info']['accumu_time'])){
				if($res['user_info']['accumu_time'] == 60*60*self::MAX_ONLINE_TIME){
					$report_type = 2;
				}else{
					$report_type = 1;
					$pdo = Env::getDbConnectionForUserRead($params['pid']);
					$user_online_time = UserOnlineTime::findBy(array('pid'=>$params['pid']),$pdo);
					if($user_online_time->is_alert == 1){
						$report_type = 2;
					}else{
						$user_online_time->pid = $params['pid'];
						$user_online_time->is_alert = 1;
						$user_online_time->update($pdo);	
					}
				}
			}

			$body_info['remind_info'][] = array(
				"account_id" => $open_id,
        "character_id" => "",
        "report_type" => $report_type,//弹窗类型，1代表单次提醒，2代表累计提醒
        "report_time" => time()
			);

			$report_reminded = CheckOnlineNotice::OnlineNoticeApi($body_info,self::MSG_TYPE_REPORT_REMINDED_BATCH);
			$res = json_decode($report_reminded,1);
			if($res == null){
				return json_encode(array(
					'res' => RespCode::UNKNOWN_ERROR
				));
			}
			if($res['comm_rsp']['ret'] != 0){
				return json_encode(array(
					"res" => $res['comm_rsp']['ret']
				));
				$logger->log($res['comm_rsp']['err_msg'],Zend_Log::DEBUG);
			}
			return json_encode(array(
					'res' => RespCode::SUCCESS
				));
		}elseif ($params['msg_type'] == self::MSG_TYPE_PUSH_EDNGAME) {
			// 用户退出游戏接口
			$body_info = array(
				"account_id" => $open_id,
        "character_id" => "",
        "this_period_time" => 300
			);

			$push_endgame = CheckOnlineNotice::OnlineNoticeApi($body_info,self::MSG_TYPE_PUSH_EDNGAME);
			$res = json_decode($push_endgame,1);
			if($res == null){
				return json_encode(array(
					'res' => RespCode::UNKNOWN_ERROR
				));
			}
			if($res['comm_rsp']['ret'] != 0){
				return json_encode(array(
					"res" => $res['comm_rsp']['ret']
				));
					$logger->log($res['comm_rsp']['err_msg'],Zend_Log::DEBUG);
			}
			
			return json_encode(array(
				"res" => RespCode::SUCCESS
			));

		}
		
	}
	
}