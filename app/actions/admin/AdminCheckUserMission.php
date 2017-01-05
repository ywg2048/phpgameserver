<?php
/**
 * Admin用：ユーザーミッション状態確認
 */
class AdminCheckUserMission extends AdminBaseAction {
	public function action($params) {
		if(isset($params['pid']))
		{
			$user_id	= $params['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		}
		else
		{
			$openid		= $params['ten_oid'];
			$type		= $params['t'];
			$user_id	= UserDevice::getUserIdFromUserOpenId($type,$openid);
		}
		$unregistered = '<span class="caution">未登録</span>';

		//--------------------------------------------------
		// PDO
		//--------------------------------------------------

		$pdo_user	= Env::getDbConnectionForUserRead($user_id);
		$pdo_share	= Env::getDbConnectionForShareRead();

		//--------------------------------------------------
		// ユーザ詳細情報を取得
		//--------------------------------------------------
		$user = User::find($user_id,$pdo_user,true);
		if($user == false)
		{
			$userInfo = array(
					'ユーザ詳細情報' => $unregistered,
			);
		}
		else {
			$userInfo = array(
					'プレイヤー名'				=> $user->name,
					'最終ログイン日時'			=> $user->li_last,
					'連続ログイン日数'			=> $user->li_str,
					//'連続ログインミッション報酬受け取り日時'				=> $user->li_mission_date,
			);
		}

 		// ミッション内容取得
		$missions = Mission::findAllBy(array());

		$missionTypes = self::getMissionTypes();
		
		// ユーザーミッション状況取得
		$all_user_missions = UserMission::getByUserId($user_id, null, $pdo_user);
		$user_missions = array();
		foreach ($all_user_missions as $user_mission) {
			$user_missions[$user_mission->mission_id] = $user_mission;
		}

		$user_mission_array = array(
			array('id', 'ミッション名', '説明文', 'ミッション種類', 'ミッション状況', '進捗', '受注日時'),
		);
		foreach ($missions as $mission) {
			$mission_id = $mission->id;
			
			$status = 0;
			$progress = '-/-';
			$ordered_at = '0000-00-00 00:00:00';
			if (array_key_exists($mission_id, $user_missions)) {
				$user_mission = $user_missions[$mission_id];
				$status = $user_mission->status;
				$progress = $user_mission->progress_num . '/' . $user_mission->progress_max;
				$ordered_at = $user_mission->ordered_at;
			}
			
			$user_mission_array[] = array(
					$mission_id,
					$mission->name,
					$mission->description,
					self::getNameFromArray($mission->mission_type, $missionTypes),
					$status,
					$progress,
					$ordered_at,
					$mission->prev_id,
					$mission->del_flg,
			);
				
		}

		//--------------------------------------------------
		// レスポンス整形
		//--------------------------------------------------
		$result = array(
				'format' => 'array',
				'ユーザ情報'				=> $userInfo,
				'ミッション <span style="color:#cc0000;">（状態変更のみで報酬付与はされません）</span>'				=> array(
						'format' => 'html',
						self::getFormList($user_id, $user_mission_array),
					),
		);


		return json_encode ( $result );
	}

	static private function getFormList($user_id, $requestParams)
	{
		$formList	= '<table border="1" style="margin:10px 5px 10px 10px;">';
		foreach($requestParams as $_key => $_value)
		{
			$mission_id			= $_value[0];
			$mission_name		= $_value[1];
			$mission_desc		= $_value[2];
			$mission_type		= $_value[3];
			$mission_status		= $_value[4];
			$mission_progress	= $_value[5];
			$mission_ordered	= $_value[6];
			$update_button	    = '';
			
			if ($_key == 0) {
				$tr		= '<tr style="background:#ffffcc;">';
				$trend	= '</tr>';
				$td		= '<th>';
				$tdend	= '</th>';
			}
			else {
				$tr		= '<tr>';
				$tr		.= '<form action="'.REQUEST_URL_ADMIN.'" method="post">';
				$tr		.= '<input type="hidden" name="action" value="admin_update_user_mission" />';
				$tr		.= '<input type="hidden" name="pid" value="'. $user_id .'" />';
				$tr		.= '<input type="hidden" name="mid" value="'. $mission_id .'" />';
				$trend	= '</form></tr>';
				
				$td		= '<td>';
				$tdend	= '</td>';
				
				$mission_prev_id	= $_value[7];
				$mission_del_flg	= $_value[8];
				
				$temp_status	= '<select name="status" ' . (($mission_prev_id < 0) ? 'disabled':'') . '>'
								. '<option value="' . UserMission::STATE_NONE . '" '. (($mission_status == UserMission::STATE_NONE) ? 'selected':'') .'>未開放</option>'
								. '<option value="' . UserMission::STATE_CHALLENGE . '" '. (($mission_status == UserMission::STATE_CHALLENGE) ? 'selected':'') .'>挑戦中</option>'
								. '<option value="' . UserMission::STATE_CLEAR . '" '. (($mission_status == UserMission::STATE_CLEAR) ? 'selected':'') .'>クリア</option>'
								. '<option value="' . UserMission::STATE_RECEIVED . '" '. (($mission_status == UserMission::STATE_RECEIVED) ? 'selected':'') .'>報酬受け取り済み</option>'
								. '</select>';
				
				$mission_status = $temp_status;
				
				$mission_ordered = '<input type="text" name="ordered_at" value="'.$mission_ordered.'" />';
				
				if ($mission_del_flg) {
					$td		= '<td style="background:#999999;">';
				}
				else {
					$update_button = '<input type="hidden" name="backlink" value="1" /><input type="submit" value="更新" />';
				}
				
			}
				
			$formList	.= $tr
						. $td . $mission_id . $tdend
						. $td . $mission_name . $tdend
						. $td . $mission_desc . $tdend
						. $td . $mission_type . $tdend
						. $td . $mission_status . $tdend
						. $td . $mission_progress . $tdend
						. $td . $mission_ordered . $tdend
						. $td . $update_button . $tdend
						. $trend;
		}
		$formList .= '</table>';
		return $formList;
	}
}
